<?php

namespace App\Http\Controllers;

use App\Models\BudgetItem;
use App\Models\Guest;
use App\Support\CurrentWedding;
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
