<x-mail::message>
# Thank you — your order is ready 🤍

You purchased **{{ $order->product_name }}**. Your print-ready files are waiting for you:

<x-mail::button :url="$downloadUrl">
Download your files
</x-mail::button>

Want to make them yours? Open the personaliser to add your names, date and venue, pick a colour, and export any time:

<x-mail::button :url="$personalizerUrl" color="success">
Open the personaliser
</x-mail::button>

A few notes:

- Your download link works for **{{ $linkDays }} days** — save the files somewhere safe.
- If the link expires, just reply to this email and we'll send a fresh one.
- The files are for your own wedding (personal use). Print as many copies as you need.

With love,<br>
The {{ config('app.name') }} studio
</x-mail::message>
