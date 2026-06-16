@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (trim($slot) === 'Laravel')
<img src="https://laravel.com/img/notification-logo-v2.1.png" class="logo" alt="Laravel Logo">
@else
{!! $slot !!}
@endif
</a>
<div style="margin-top: 4px; font-size: 11px; letter-spacing: 2px; text-transform: uppercase; color: #b3a78c;">The wedding planning studio &amp; vendor marketplace</div>
</td>
</tr>
