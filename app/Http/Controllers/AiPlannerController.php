<?php

namespace App\Http\Controllers;

use App\Enums\EventType;
use App\Enums\PermissionLevel;
use App\Enums\Section;
use App\Enums\TaskCategory;
use App\Enums\TaskPriority;
use App\Models\AiChatMessage;
use App\Models\BudgetCategory;
use App\Models\BudgetItem;
use App\Models\Task;
use App\Models\TimelineEvent;
use App\Services\PermissionService;
use App\Support\Ai\AiException;
use App\Support\Ai\AiService;
use App\Support\CurrentWedding;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * AI "Plan Starter" — generates an editable checklist, budget, or day-of
 * timeline from the couple's own wedding details, so nothing has to be filled
 * in from a blank page. Generation only proposes; the couple edits and then
 * applies, and apply is what writes to the database (never a silent AI write).
 */
class AiPlannerController extends Controller
{
    /** Upper bound on items we'll generate/apply in one go (cost + abuse guard). */
    protected const MAX_ITEMS = 60;

    /** The three generators, each tied to the workspace section it writes to. */
    protected const KINDS = ['checklist', 'budget', 'timeline'];

    /** How many recent turns of the conversation to send the model (cost guard). */
    protected const CHAT_HISTORY = 20;

    public function __construct(
        protected CurrentWedding $current,
        protected PermissionService $permissions,
        protected AiService $ai,
    ) {}

    /**
     * The assistant landing page — three generators, each with an editable
     * preview before anything is saved.
     */
    public function index(): Response
    {
        $wedding = $this->current->get();
        abort_unless($wedding !== null, 403, 'No active wedding.');

        $user = request()->user();

        return Inertia::render('assistant/index', [
            'configured' => $this->ai->isConfigured(),
            // AI is a paid perk; free couples see an upgrade prompt instead.
            'entitled' => $user->canUseAi(),
            'wedding' => [
                'name' => $wedding->name,
                'event_date' => $wedding->event_date?->toDateString(),
                'guest_count' => $wedding->guests()->count(),
            ],
            // Which generators this user may actually apply (write permission).
            'can' => [
                'checklist' => $this->permissions->canWrite($user, $wedding, Section::Checklist),
                'budget' => $this->permissions->canWrite($user, $wedding, Section::Budget),
                'timeline' => $this->permissions->canWrite($user, $wedding, Section::Timeline),
            ],
            'options' => [
                'task_categories' => $this->labelled(TaskCategory::cases()),
                'task_priorities' => $this->labelled(TaskPriority::cases()),
                'event_types' => $this->labelled(EventType::cases()),
            ],
            // The persisted conversation with the AI planner (shared across collaborators).
            'history' => AiChatMessage::forWedding($wedding->id)->ordered()->get()
                ->map(fn (AiChatMessage $m) => ['id' => $m->id, 'role' => $m->role, 'content' => $m->content])
                ->values(),
        ]);
    }

    /**
     * One conversational turn with the AI wedding planner. Persists the exchange
     * (so the chat is an ongoing partner) and always answers JSON — including on
     * failure — so the front-end never sees a non-JSON 500.
     */
    public function chat(Request $request): JsonResponse
    {
        $wedding = $this->current->get();
        abort_unless($wedding !== null, 403, 'No active wedding.');

        if (! $request->user()->canUseAi()) {
            return response()->json([
                'message' => 'AI assistance is a paid feature. Upgrade your plan to unlock it.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'message' => ['required', 'string', 'max:2000'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        if (! $this->ai->isConfigured()) {
            return response()->json(['available' => false]);
        }

        $text = trim($validator->validated()['message']);

        // Recent history from the DB + this new turn (not yet persisted — we only
        // save the pair once the model actually answers, so failures leave no
        // orphaned half-conversation behind).
        $history = AiChatMessage::forWedding($wedding->id)->ordered()->get()
            ->slice(-self::CHAT_HISTORY)
            ->map(fn (AiChatMessage $m) => ['role' => $m->role, 'content' => $m->content])
            ->values()
            ->all();
        $history[] = ['role' => 'user', 'content' => $text];

        try {
            $reply = $this->ai->chat($this->chatSystemPrompt($wedding), $history);
        } catch (AiException $e) {
            return response()->json(['available' => true, 'ok' => false, 'reply' => $e->getMessage()]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'available' => true,
                'ok' => false,
                'reply' => 'Sorry — I had trouble answering just now. Please try again in a moment.',
            ]);
        }

        AiChatMessage::create(['wedding_id' => $wedding->id, 'role' => 'user', 'content' => $text]);
        $saved = AiChatMessage::create(['wedding_id' => $wedding->id, 'role' => 'assistant', 'content' => $reply]);

        return response()->json(['available' => true, 'ok' => true, 'reply' => $reply, 'id' => $saved->id]);
    }

    /** Clear the conversation so the couple can start fresh. */
    public function resetChat(Request $request): RedirectResponse
    {
        $wedding = $this->current->get();
        abort_unless($wedding !== null, 403, 'No active wedding.');
        abort_unless($request->user()->canUseAi(), 403, 'AI assistance is a paid feature.');

        AiChatMessage::forWedding($wedding->id)->delete();

        return back();
    }

    /**
     * Produce an AI proposal for the requested kind and return it as JSON for
     * the in-page preview (no navigation). Nothing is persisted here.
     */
    public function generate(Request $request): JsonResponse
    {
        $wedding = $this->current->get();
        abort_unless($wedding !== null, 403, 'No active wedding.');

        // This endpoint is consumed by a fetch() preview, so always answer JSON
        // (including on validation failure) rather than the HTML redirect path.
        $validator = Validator::make($request->all(), [
            'kind' => ['required', Rule::in(self::KINDS)],
            'notes' => ['nullable', 'string', 'max:1000'],
            'total_budget' => ['nullable', 'numeric', 'min:0', 'max:100000000'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        if (! $request->user()->canUseAi()) {
            return response()->json([
                'message' => 'AI assistance is a paid feature. Upgrade your plan to unlock it.',
            ], 403);
        }

        $data = $validator->validated();
        $kind = $data['kind'];
        $this->authorizeWrite($kind);

        if (! $this->ai->isConfigured()) {
            return response()->json([
                'message' => 'AI assistance is not configured on this server.',
            ], 422);
        }

        try {
            $items = match ($kind) {
                'checklist' => $this->generateChecklist($wedding, $data),
                'budget' => $this->generateBudget($wedding, $data),
                'timeline' => $this->generateTimeline($wedding, $data),
            };
        } catch (AiException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['kind' => $kind, 'items' => $items]);
    }

    /**
     * Persist the couple-reviewed items. This is the only write path; it does
     * not call the AI, so it is fully testable and the couple is always in
     * control of what lands in their workspace.
     */
    public function apply(Request $request): RedirectResponse
    {
        $wedding = $this->current->get();
        abort_unless($wedding !== null, 403, 'No active wedding.');

        abort_unless($request->user()->canUseAi(), 403, 'AI assistance is a paid feature.');

        $kind = $request->input('kind');
        abort_unless(in_array($kind, self::KINDS, true), 422);

        $this->authorizeWrite($kind);

        $count = match ($kind) {
            'checklist' => $this->applyChecklist($request),
            'budget' => $this->applyBudget($request),
            'timeline' => $this->applyTimeline($request),
        };

        return back()->with('status', "ai-applied-{$kind}-{$count}");
    }

    // ── Checklist ────────────────────────────────────────────────────────────

    /** @return array<int, array<string,mixed>> */
    protected function generateChecklist($wedding, array $data): array
    {
        $tool = [
            'name' => 'propose_checklist',
            'description' => 'Return a wedding planning checklist tailored to the couple.',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'tasks' => [
                        'type' => 'array',
                        'description' => 'Planning tasks, ordered earliest-due first.',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'title' => ['type' => 'string', 'description' => 'Short, actionable task title.'],
                                'category' => ['type' => 'string', 'enum' => TaskCategory::values()],
                                'priority' => ['type' => 'string', 'enum' => TaskPriority::values()],
                                'months_before' => [
                                    'type' => 'integer',
                                    'description' => 'How many months before the wedding this is typically done (0 = wedding month).',
                                ],
                            ],
                            'required' => ['title', 'category', 'priority', 'months_before'],
                        ],
                    ],
                ],
                'required' => ['tasks'],
            ],
        ];

        $system = 'You are an expert Ontario, Canada wedding planner. Produce a practical, '
            .'comprehensive planning checklist. Use realistic Canadian timelines and vendor types. '
            .'Be specific and actionable. Avoid duplicates. Return 20-35 tasks.';

        $result = $this->ai->generateStructured($system, $this->context($wedding, $data), $tool);

        return collect($result['tasks'] ?? [])
            ->take(self::MAX_ITEMS)
            ->map(fn ($t) => [
                'title' => (string) ($t['title'] ?? ''),
                'category' => in_array($t['category'] ?? null, TaskCategory::values(), true)
                    ? $t['category'] : TaskCategory::Planning->value,
                'priority' => in_array($t['priority'] ?? null, TaskPriority::values(), true)
                    ? $t['priority'] : TaskPriority::Medium->value,
                'months_before' => max(0, min(36, (int) ($t['months_before'] ?? 0))),
            ])
            ->filter(fn ($t) => $t['title'] !== '')
            ->values()
            ->all();
    }

    protected function applyChecklist(Request $request): int
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:'.self::MAX_ITEMS],
            'items.*.title' => ['required', 'string', 'max:255'],
            'items.*.category' => ['required', Rule::in(TaskCategory::values())],
            'items.*.priority' => ['required', Rule::in(TaskPriority::values())],
            'items.*.months_before' => ['nullable', 'integer', 'min:0', 'max:36'],
        ]);

        $weddingId = $this->current->id();
        $eventDate = $this->current->get()->event_date;

        return DB::transaction(function () use ($data, $weddingId, $eventDate) {
            $count = 0;

            foreach ($data['items'] as $item) {
                $due = null;
                if ($eventDate !== null && isset($item['months_before'])) {
                    $due = $eventDate->copy()->subMonthsNoOverflow((int) $item['months_before']);
                }

                Task::create([
                    'wedding_id' => $weddingId,
                    'title' => $item['title'],
                    'category' => $item['category'],
                    'priority' => $item['priority'],
                    'due_date' => $due,
                    'is_complete' => false,
                ]);
                $count++;
            }

            return $count;
        });
    }

    // ── Budget ───────────────────────────────────────────────────────────────

    /** @return array<int, array<string,mixed>> */
    protected function generateBudget($wedding, array $data): array
    {
        $hasTotal = isset($data['total_budget']) && (float) $data['total_budget'] > 0;

        $tool = [
            'name' => 'propose_budget',
            'description' => 'Return a starter wedding budget broken into categories and line items.',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'items' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'category' => ['type' => 'string', 'description' => 'Budget category, e.g. "Venue", "Catering", "Photography".'],
                                'name' => ['type' => 'string', 'description' => 'Line item within the category.'],
                                'estimated_dollars' => ['type' => 'number', 'description' => 'Estimated cost in CAD dollars (whole dollars).'],
                            ],
                            'required' => ['category', 'name', 'estimated_dollars'],
                        ],
                    ],
                ],
                'required' => ['items'],
            ],
        ];

        $system = 'You are an expert Ontario, Canada wedding-budget planner. Build a realistic starter '
            .'budget using current Ontario market prices in Canadian dollars. Group line items under clear '
            .'categories (Venue, Catering, Photography, Attire, Flowers, Music, Stationery, etc.). '
            .($hasTotal
                ? 'The line items must sum to approximately the couple\'s stated total budget.'
                : 'Estimate sensible absolute amounts for a typical Ontario wedding.')
            .' Return 12-24 line items.';

        $result = $this->ai->generateStructured($system, $this->context($wedding, $data), $tool);

        return collect($result['items'] ?? [])
            ->take(self::MAX_ITEMS)
            ->map(fn ($i) => [
                'category' => trim((string) ($i['category'] ?? 'Other')) ?: 'Other',
                'name' => (string) ($i['name'] ?? ''),
                'estimated_cents' => (int) round(max(0, (float) ($i['estimated_dollars'] ?? 0)) * 100),
            ])
            ->filter(fn ($i) => $i['name'] !== '')
            ->values()
            ->all();
    }

    protected function applyBudget(Request $request): int
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:'.self::MAX_ITEMS],
            'items.*.category' => ['required', 'string', 'max:120'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.estimated_cents' => ['required', 'integer', 'min:0', 'max:1000000000'],
        ]);

        $weddingId = $this->current->id();

        return DB::transaction(function () use ($data, $weddingId) {
            // Reuse existing categories by name; create the rest once up front.
            $existing = BudgetCategory::query()
                ->where('wedding_id', $weddingId)
                ->pluck('id', 'name');

            $nextSort = (int) BudgetCategory::query()->where('wedding_id', $weddingId)->max('sort_order');
            $count = 0;

            foreach ($data['items'] as $item) {
                $categoryName = Str::limit(trim($item['category']), 120, '');

                if (! isset($existing[$categoryName])) {
                    $category = BudgetCategory::create([
                        'wedding_id' => $weddingId,
                        'name' => $categoryName,
                        'sort_order' => ++$nextSort,
                    ]);
                    $existing[$categoryName] = $category->id;
                }

                BudgetItem::create([
                    'wedding_id' => $weddingId,
                    'category_id' => $existing[$categoryName],
                    'name' => $item['name'],
                    'estimated_cents' => (int) $item['estimated_cents'],
                ]);
                $count++;
            }

            return $count;
        });
    }

    // ── Timeline ─────────────────────────────────────────────────────────────

    /** @return array<int, array<string,mixed>> */
    protected function generateTimeline($wedding, array $data): array
    {
        $tool = [
            'name' => 'propose_timeline',
            'description' => 'Return a wedding-day run-of-show timeline.',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'events' => [
                        'type' => 'array',
                        'description' => 'Day-of events in chronological order.',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'title' => ['type' => 'string'],
                                'type' => ['type' => 'string', 'enum' => EventType::values()],
                                'time' => ['type' => 'string', 'description' => '24-hour clock time, "HH:MM".'],
                                'location' => ['type' => 'string', 'description' => 'Optional location/room.'],
                            ],
                            'required' => ['title', 'type', 'time'],
                        ],
                    ],
                ],
                'required' => ['events'],
            ],
        ];

        $system = 'You are an expert Ontario, Canada wedding-day coordinator. Produce a realistic '
            .'wedding-day run-of-show from getting-ready through the send-off, with sensible times and '
            .'durations. Use 24-hour times. Return 10-18 events in chronological order.';

        $result = $this->ai->generateStructured($system, $this->context($wedding, $data), $tool);

        return collect($result['events'] ?? [])
            ->take(self::MAX_ITEMS)
            ->map(fn ($e) => [
                'title' => (string) ($e['title'] ?? ''),
                'type' => in_array($e['type'] ?? null, EventType::values(), true)
                    ? $e['type'] : EventType::Other->value,
                'time' => $this->normalizeTime((string) ($e['time'] ?? '')),
                'location' => isset($e['location']) ? Str::limit((string) $e['location'], 255, '') : null,
            ])
            ->filter(fn ($e) => $e['title'] !== '' && $e['time'] !== null)
            ->values()
            ->all();
    }

    protected function applyTimeline(Request $request): int
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:'.self::MAX_ITEMS],
            'items.*.title' => ['required', 'string', 'max:255'],
            'items.*.type' => ['required', Rule::in(EventType::values())],
            'items.*.time' => ['required', 'string', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'items.*.location' => ['nullable', 'string', 'max:255'],
        ]);

        $weddingId = $this->current->id();
        // Anchor to the wedding date so the day-of ordering is correct; if the
        // date is unknown yet, anchor to today purely so the times sort.
        $anchor = $this->current->get()->event_date?->copy() ?? Carbon::today();

        return DB::transaction(function () use ($data, $weddingId, $anchor) {
            $count = 0;

            foreach ($data['items'] as $item) {
                [$h, $m] = explode(':', $item['time']);
                $startsAt = $anchor->copy()->setTime((int) $h, (int) $m);

                TimelineEvent::create([
                    'wedding_id' => $weddingId,
                    'title' => $item['title'],
                    'type' => $item['type'],
                    'starts_at' => $startsAt,
                    'location' => $item['location'] ?? null,
                ]);
                $count++;
            }

            return $count;
        });
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Build the shared wedding-context prompt the model reasons from.
     */
    protected function context($wedding, array $data): string
    {
        $lines = ["Couple/wedding name: {$wedding->name}"];

        if ($wedding->event_date !== null) {
            $months = now()->diffInMonths($wedding->event_date, false);
            $lines[] = 'Wedding date: '.$wedding->event_date->format('F j, Y')
                .($months >= 0 ? " (about {$months} months away)" : ' (already passed)');
        } else {
            $lines[] = 'Wedding date: not set yet.';
        }

        $guestCount = $wedding->guests()->count();
        if ($guestCount > 0) {
            $lines[] = "Approximate guest count: {$guestCount}.";
        }

        if (isset($data['total_budget']) && (float) $data['total_budget'] > 0) {
            $lines[] = 'Total budget: CAD $'.number_format((float) $data['total_budget']).'.';
        }

        if (filled($data['notes'] ?? null)) {
            $lines[] = 'Additional context from the couple: '.trim($data['notes']);
        }

        return implode("\n", $lines);
    }

    /**
     * The system prompt for the conversational planner: personalised with this
     * wedding's details, scoped to wedding planning, and held to the same clean,
     * concise house style as the rest of the product.
     */
    protected function chatSystemPrompt($wedding): string
    {
        $context = $this->context($wedding, []);

        return <<<PROMPT
        You are VowNook's AI wedding planner — a warm, knowledgeable planning partner for a couple
        using VowNook, a wedding-planning studio and Ontario (Canada) wedding-vendor marketplace.

        What you know about this wedding:
        {$context}

        Help with anything wedding-planning: budgeting, timelines, checklists, the guest list and
        RSVPs, seating, vendors, décor, etiquette, wording, and day-of logistics — grounded in
        Ontario, Canada and realistic Canadian prices and timelines. Use the details above to make
        advice specific to this couple, and ask a short clarifying question when it would help.

        VowNook tools you can point them to by name: Checklist, Budget, Guests (RSVPs + meal options),
        Timeline, Floor plan (seating), Website (publish + share), Registry, Marketplace (find & book
        vendors), and Collaborators. When a full starting point would help — a complete checklist,
        budget, or day-of timeline — suggest the "Plan Starter" on this page, which drafts editable
        items in one click.

        STYLE (important)
        - Conversational and concise: a few short sentences, or a short bullet list for steps.
        - Use **bold** only for tool/section names or key figures. No headings, no emojis, no tables.
        - Stay on this couple's wedding planning. For account-specific issues (a charge, a bug),
          gently point them to Help & support instead.
        - Never invent VowNook features or prices that aren't described above.
        PROMPT;
    }

    /** Coerce a loose time string into strict "HH:MM" 24-hour, or null. */
    protected function normalizeTime(string $raw): ?string
    {
        $raw = trim($raw);

        if (preg_match('/^([01]?\d|2[0-3]):([0-5]\d)$/', $raw, $m)) {
            return str_pad($m[1], 2, '0', STR_PAD_LEFT).':'.$m[2];
        }

        // Accept "7:30 PM" style as a fallback.
        $ts = strtotime($raw);

        return $ts !== false ? date('H:i', $ts) : null;
    }

    /**
     * Guard: the active user must hold write permission on the section the
     * requested kind writes to.
     */
    protected function authorizeWrite(string $kind): void
    {
        $section = match ($kind) {
            'checklist' => Section::Checklist,
            'budget' => Section::Budget,
            'timeline' => Section::Timeline,
        };

        abort_unless(
            $this->permissions->allows(
                request()->user(),
                $this->current->get(),
                $section,
                PermissionLevel::Write,
            ),
            403,
            'You do not have permission to use this generator.',
        );
    }

    /**
     * @param  array<int, \BackedEnum>  $cases
     * @return array<int, array{value:string,label:string}>
     */
    protected function labelled(array $cases): array
    {
        return array_map(
            fn ($c) => ['value' => $c->value, 'label' => method_exists($c, 'label') ? $c->label() : $c->name],
            $cases,
        );
    }
}
