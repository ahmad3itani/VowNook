<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #1e1b17; font-size: 11px; margin: 0; }
        h1 { font-size: 22px; margin: 0 0 2px; color: #1e1b18; }
        .subtitle { color: #775a19; font-size: 11px; text-transform: uppercase; letter-spacing: .12em; margin-bottom: 18px; }
        .tables { width: 100%; }
        .table-card { width: 47%; display: inline-block; vertical-align: top; border: 1px solid #cec5bd; margin: 0 1% 14px; padding: 12px 14px; }
        .table-head { border-bottom: 1px solid #efe7e0; padding-bottom: 6px; margin-bottom: 8px; }
        .table-name { font-size: 14px; color: #1e1b18; }
        .table-meta { color: #6f675e; font-size: 9px; text-transform: uppercase; letter-spacing: .08em; }
        .seat { padding: 3px 0; border-bottom: 1px solid #f4ece6; }
        .seat-no { display: inline-block; width: 18px; color: #775a19; font-weight: bold; }
        .meal { color: #a8a29e; font-size: 9px; }
        .muted { color: #a8a29e; }
        .unseated { margin-top: 8px; border-top: 1px solid #cec5bd; padding-top: 12px; }
        .footer { margin-top: 18px; text-align: center; color: #a8a29e; font-size: 10px; }
    </style>
</head>
<body>
    <h1>{{ $wedding->name }}</h1>
    <div class="subtitle">
        Seating Chart{{ $wedding->event_date ? ' — '.$wedding->event_date->format('F j, Y') : '' }}
    </div>

    <div class="tables">
        @forelse ($tables as $table)
            <div class="table-card">
                <div class="table-head">
                    <span class="table-name">{{ $table['name'] }}</span><br>
                    <span class="table-meta">{{ $table['shape'] }} · {{ count($table['guests']) }}/{{ $table['capacity'] }} seated</span>
                </div>
                @forelse ($table['guests'] as $g)
                    <div class="seat">
                        <span class="seat-no">{{ $g['seat'] ?? '·' }}</span>
                        {{ $g['name'] }}
                        @if ($g['meal'])<span class="meal"> — {{ $g['meal'] }}</span>@endif
                    </div>
                @empty
                    <div class="muted">No one seated yet.</div>
                @endforelse
            </div>
        @empty
            <p class="muted">No tables have been created.</p>
        @endforelse
    </div>

    @if ($unseated->isNotEmpty())
        <div class="unseated">
            <span class="table-meta">Not yet seated ({{ $unseated->count() }})</span>
            <p>{{ $unseated->implode(' · ') }}</p>
        </div>
    @endif

    <div class="footer">Prepared with WedFlow Atelier</div>
</body>
</html>
