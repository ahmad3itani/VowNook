<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The free wedding-budget cheat sheet the shop's opt-in promises. A single
 * requested send (express CASL consent), not a recurring newsletter.
 */
class ShopBudgetCheatSheet extends Mailable
{
    use Queueable, SerializesModels;

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your wedding budget cheat sheet 🤍',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.shop-budget-cheat-sheet',
        );
    }
}
