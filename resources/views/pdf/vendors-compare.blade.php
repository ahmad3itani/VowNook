<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #12211b; font-size: 11px; margin: 0; }
        h1 { font-size: 22px; margin: 0 0 2px; color: #12211b; }
        .subtitle { color: #1b4638; font-size: 11px; text-transform: uppercase; letter-spacing: .12em; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; border-bottom: 2px solid #d5d8d1; padding: 7px 8px; font-size: 9px; text-transform: uppercase; letter-spacing: .08em; color: #5c6a62; }
        td { border-bottom: 1px solid #e4e8e0; padding: 7px 8px; vertical-align: top; }
        .name { font-size: 13px; color: #12211b; }
        .best { background: #eef1eb; }
        .badge { display: inline-block; background: #1b4638; color: #fff; font-size: 8px; text-transform: uppercase; letter-spacing: .1em; padding: 2px 6px; }
        .muted { color: #a8a29e; }
        .gold { color: #1b4638; }
        .footer { margin-top: 24px; text-align: center; color: #a8a29e; font-size: 10px; }
    </style>
</head>
<body>
    <h1>{{ $wedding->name }}</h1>
    <div class="subtitle">{{ $category }} — Vendor Comparison</div>

    <table>
        <thead>
            <tr>
                <th>Vendor</th>
                <th>Rating</th>
                <th>Price</th>
                <th>Est. cost</th>
                <th>Status</th>
                <th>Contact</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($vendors as $v)
                <tr @class(['best' => $v['id'] === $bestValueId])>
                    <td>
                        <span class="name">{{ $v['name'] }}</span>
                        @if ($v['id'] === $bestValueId)<br><span class="badge">Best value</span>@endif
                    </td>
                    <td class="gold">{{ $v['rating'] ? str_repeat('★', $v['rating']).str_repeat('☆', 5 - $v['rating']) : '—' }}</td>
                    <td>{{ $v['price_level'] ? str_repeat('$', $v['price_level']) : '—' }}</td>
                    <td>@if ($v['cost'] !== null){{ '$'.number_format($v['cost'], 0) }}@else<span class="muted">—</span>@endif</td>
                    <td>{{ ucfirst($v['status']) }}</td>
                    <td>
                        {{ $v['contact_name'] ?? '—' }}
                        @if ($v['email'])<br><span class="muted">{{ $v['email'] }}</span>@endif
                        @if ($v['phone'])<br><span class="muted">{{ $v['phone'] }}</span>@endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">No vendors in this category.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">Prepared with VowNook</div>
</body>
</html>
