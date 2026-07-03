<x-mail::message>
# Your wedding budget cheat sheet

You asked, here it is — the honest starting point we give every couple. Take your total budget and divide it roughly like this:

<x-mail::table>
| Where it goes | Share | On a $30,000 wedding |
|:--|:--|:--|
| Venue & catering | 45–50% | $13,500–15,000 |
| Photography & video | 10–12% | $3,000–3,600 |
| Music & entertainment | 5–8% | $1,500–2,400 |
| Flowers & décor | 8–10% | $2,400–3,000 |
| Attire & beauty | 7–9% | $2,100–2,700 |
| Stationery & signage | 2–3% | $600–900 |
| Officiant, transport & extras | 4–6% | $1,200–1,800 |
| **Buffer — do not skip this** | **10%** | **$3,000** |
</x-mail::table>

Three rules that save couples the most money:

1. **Book the big three first** — venue, catering, photography set the tone and eat two-thirds of the budget. Lock them before spending a dollar elsewhere.
2. **The buffer is sacred.** Almost every wedding lands 8–12% over its plan (alterations, overtime, last-minute guests). Budgeting the overrun up front is what keeps it painless.
3. **Track estimated vs. actual, per vendor.** The gap between "quoted" and "final invoice" is where budgets die quietly.

The easiest way to do all three is the free VowNook budget tracker — estimates, actuals, deposits and what's still owed, per vendor:

<x-mail::button :url="config('app.url')">
Start your budget — free
</x-mail::button>

And since you found us through the stationery shop: editable invitation suites from the studio start at $14, matched across your whole day.

<x-mail::button url="{{ config('app.url') }}/shop" color="success">
Browse the collection
</x-mail::button>

With love,<br>
The {{ config('app.name') }} studio

<x-slot:subcopy>
You received this one-time email because you requested the budget cheat sheet at {{ config('app.name') }}. We won't email you again unless you ask us to.
</x-slot:subcopy>
</x-mail::message>
