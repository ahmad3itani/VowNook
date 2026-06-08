<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #1e1b17; font-size: 11px; margin: 0; }
        h1 { font-size: 20px; margin: 0 0 2px; color: #1e1b18; }
        .subtitle { color: #775a19; font-size: 10px; text-transform: uppercase; letter-spacing: .12em; margin-bottom: 12px; }
        .canvas { position: relative; border: 1.5px solid #b9ab97; background: #efe7da; margin: 0 auto; }
        .el { position: absolute; border: 1px solid #9c8f7d; background: #e6d8bd; color: #5b4a1f; font-size: 8px; text-align: center; overflow: hidden; }
        .table { position: absolute; border: 1.5px solid #3d3833; background: #ffffff; text-align: center; color: #1e1b18; font-size: 8px; font-weight: bold; overflow: hidden; }
        .chair { position: absolute; border-radius: 50%; text-align: center; }
        .chair-on { background: #775a19; color: #ffffff; }
        .chair-off { background: #ffffff; border: 1px solid #9c8f7d; color: #7d7468; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; border-bottom: 1.5px solid #cec5bd; padding: 6px 8px; font-size: 9px; text-transform: uppercase; letter-spacing: .06em; color: #6f675e; }
        td { border-bottom: 1px solid #f4ece6; padding: 6px 8px; vertical-align: top; }
        .seat-no { color: #775a19; font-weight: bold; }
        .allergy { color: #b4524a; font-size: 10px; }
        .muted { color: #a8a29e; }
        .table-title { font-size: 13px; color: #1e1b18; margin: 14px 0 4px; }
        .footer { margin-top: 14px; text-align: center; color: #a8a29e; font-size: 9px; }
    </style>
</head>
<body>
    {{-- PAGE 1 — visual floor plan --}}
    <h1>{{ $wedding->name }}</h1>
    <div class="subtitle">Floor Plan · {{ $plan['room_width'] }} × {{ $plan['room_height'] }} ft</div>

    <div class="canvas" style="width: {{ $plan['width'] }}px; height: {{ $plan['height'] }}px;">
        @foreach ($plan['elements'] as $el)
            <div class="el" style="left: {{ $el['cx'] - $el['w'] / 2 }}px; top: {{ $el['cy'] - $el['h'] / 2 }}px; width: {{ $el['w'] }}px; height: {{ $el['h'] }}px; line-height: {{ $el['h'] }}px;">
                {{ $el['label'] }}
            </div>
        @endforeach

        @foreach ($plan['tables'] as $t)
            @foreach ($t['chairs'] as $c)
                <div class="chair {{ $c['occupied'] ? 'chair-on' : 'chair-off' }}"
                     style="left: {{ $c['x'] - $t['chair'] / 2 }}px; top: {{ $c['y'] - $t['chair'] / 2 }}px; width: {{ $t['chair'] }}px; height: {{ $t['chair'] }}px; line-height: {{ $t['chair'] }}px; font-size: {{ max(5, round($t['chair'] * 0.4)) }}px;">
                    {{ $c['label'] }}
                </div>
            @endforeach
            <div class="table"
                 style="left: {{ $t['cx'] - $t['w'] / 2 }}px; top: {{ $t['cy'] - $t['h'] / 2 }}px; width: {{ $t['w'] }}px; height: {{ $t['h'] }}px; line-height: {{ $t['h'] }}px; border-radius: {{ $t['round'] ? '50%' : '4px' }};">
                {{ $t['name'] }}
            </div>
        @endforeach
    </div>

    {{-- PAGE 2 — seating chart with meals & allergies --}}
    <div style="page-break-before: always;">
        <h1>{{ $wedding->name }}</h1>
        <div class="subtitle">Seating Chart{{ $wedding->event_date ? ' — '.$wedding->event_date->format('F j, Y') : '' }}</div>

        @forelse ($tables as $table)
            <div class="table-title">{{ $table['name'] }}
                <span class="muted" style="font-size: 9px;">· {{ $table['shape'] }} · {{ count($table['guests']) }}/{{ $table['capacity'] }}</span>
            </div>
            @if (count($table['guests']))
                <table>
                    <thead>
                        <tr>
                            <th style="width: 6%;">Seat</th>
                            <th style="width: 34%;">Guest</th>
                            <th style="width: 28%;">Meal</th>
                            <th style="width: 32%;">Allergies / dietary</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($table['guests'] as $g)
                            <tr>
                                <td class="seat-no">{{ $g['seat'] ?? '·' }}</td>
                                <td>{{ $g['name'] }}</td>
                                <td>{{ $g['meal'] ?: '—' }}</td>
                                <td>@if ($g['dietary'])<span class="allergy">{{ $g['dietary'] }}</span>@else<span class="muted">—</span>@endif</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="muted" style="margin: 2px 0 8px;">No one seated yet.</p>
            @endif
        @empty
            <p class="muted">No tables have been created.</p>
        @endforelse

        @if ($unseated->isNotEmpty())
            <div class="table-title">Not yet seated
                <span class="muted" style="font-size: 9px;">· {{ $unseated->count() }}</span>
            </div>
            <p>{{ $unseated->implode(' · ') }}</p>
        @endif

        <div class="footer">Prepared with WedFlow Atelier</div>
    </div>
</body>
</html>
