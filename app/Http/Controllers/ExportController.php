<?php

namespace App\Http\Controllers;

use App\Models\BudgetItem;
use App\Models\Guest;
use App\Models\TimelineEvent;
use App\Support\CurrentWedding;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function __construct(protected CurrentWedding $current) {}

    public function guests(): StreamedResponse
    {
        $weddingId = $this->current->id();

        $guests = Guest::query()
            ->forWedding($weddingId)
            ->with(['group:id,name', 'seatingTable:id,name'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $rows = $guests->map(fn (Guest $g) => [
            $g->first_name,
            $g->last_name,
            $g->email,
            $g->phone,
            $g->side->label(),
            $g->age_group->label(),
            $g->is_plus_one ? 'Yes' : 'No',
            $g->rsvp_status->label(),
            $g->meal_choice,
            $g->dietary_notes,
            $g->group?->name,
            $g->seatingTable?->name,
            $g->notes,
        ]);

        return $this->stream('guests', [
            'First name', 'Last name', 'Email', 'Phone', 'Side', 'Age group',
            'Plus one', 'RSVP', 'Meal choice', 'Dietary notes', 'Group', 'Table', 'Notes',
        ], $rows->all());
    }

    public function guestsPdf(): Response
    {
        $weddingId = $this->current->id();
        $wedding = $this->current->get();

        $guests = Guest::query()
            ->forWedding($weddingId)
            ->with('seatingTable:id,name')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $pdf = Pdf::loadView('pdf.guests', [
            'wedding' => $wedding,
            'guests' => $guests,
            'counts' => [
                'total' => $guests->count(),
                'attending' => $guests->where('rsvp_status', \App\Enums\RsvpStatus::Attending)->count(),
                'declined' => $guests->where('rsvp_status', \App\Enums\RsvpStatus::Declined)->count(),
                'pending' => $guests->where('rsvp_status', \App\Enums\RsvpStatus::Pending)->count(),
            ],
        ])->setPaper('a4');

        return $pdf->download(Str::slug($wedding->name).'-guest-list.pdf');
    }

    public function timelinePdf(): Response
    {
        $weddingId = $this->current->id();
        $wedding = $this->current->get();

        $events = TimelineEvent::query()
            ->forWedding($weddingId)
            ->with('vendor:id,name')
            ->orderBy('starts_at')
            ->get()
            ->groupBy(fn (TimelineEvent $e) => $e->starts_at->toDateString());

        $pdf = Pdf::loadView('pdf.timeline', [
            'wedding' => $wedding,
            'days' => $events,
        ])->setPaper('a4');

        return $pdf->download(Str::slug($wedding->name).'-timeline.pdf');
    }

    public function budget(): StreamedResponse
    {
        $weddingId = $this->current->id();

        $items = BudgetItem::query()
            ->forWedding($weddingId)
            ->with('category:id,name')
            ->orderBy('name')
            ->get();

        $rows = $items->map(fn (BudgetItem $i) => [
            $i->name,
            $i->category?->name,
            number_format($i->estimated_cents / 100, 2, '.', ''),
            $i->actual_cents !== null ? number_format($i->actual_cents / 100, 2, '.', '') : '',
            number_format($i->paid_cents / 100, 2, '.', ''),
            $i->due_date?->toDateString(),
            $i->notes,
        ]);

        return $this->stream('budget', [
            'Item', 'Category', 'Estimated', 'Actual', 'Paid', 'Due date', 'Notes',
        ], $rows->all());
    }

    public function timeline(): Response
    {
        $weddingId = $this->current->id();

        $events = TimelineEvent::query()
            ->forWedding($weddingId)
            ->with('vendor:id,name')
            ->orderBy('starts_at')
            ->get();

        $weddingName = $this->current->get()?->name ?? 'Wedding';

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//WedFlow Atelier//Timeline//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:'.$this->escapeIcs($weddingName.' — Timeline'),
        ];

        foreach ($events as $event) {
            $start = $event->starts_at->clone()->utc();
            $end = ($event->ends_at ?? $event->starts_at->clone()->addHour())->clone()->utc();

            $description = collect([
                $event->vendor?->name ? 'Vendor: '.$event->vendor->name : null,
                $event->notes,
            ])->filter()->implode("\n");

            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:timeline-'.$event->id.'@wedflow';
            $lines[] = 'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z');
            $lines[] = 'DTSTART:'.$start->format('Ymd\THis\Z');
            $lines[] = 'DTEND:'.$end->format('Ymd\THis\Z');
            $lines[] = 'SUMMARY:'.$this->escapeIcs($event->title);
            if ($event->location) {
                $lines[] = 'LOCATION:'.$this->escapeIcs($event->location);
            }
            if ($description !== '') {
                $lines[] = 'DESCRIPTION:'.$this->escapeIcs($description);
            }
            $lines[] = 'END:VEVENT';
        }

        $lines[] = 'END:VCALENDAR';

        $filename = Str::slug($weddingName).'-timeline.ics';

        return response(implode("\r\n", $lines), 200, [
            'Content-Type' => 'text/calendar; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    protected function escapeIcs(string $value): string
    {
        return str_replace(
            ['\\', ';', ',', "\r\n", "\n"],
            ['\\\\', '\\;', '\\,', '\\n', '\\n'],
            $value,
        );
    }

    /**
     * @param  list<string>  $headers
     * @param  list<array<int, mixed>>  $rows
     */
    protected function stream(string $name, array $headers, array $rows): StreamedResponse
    {
        $wedding = Str::slug($this->current->get()?->name ?? 'wedding');
        $filename = "{$wedding}-{$name}-".now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM so Excel renders accented characters correctly.
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
