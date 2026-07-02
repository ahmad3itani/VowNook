<?php

namespace App\Mail;

use App\Models\ShopOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

/**
 * The shop delivery email: a signed, expiring download link for the purchased
 * files plus the personaliser link. Sent by the Stripe webhook on fulfilment.
 */
class ShopOrderDelivery extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ShopOrder $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your VowNook order — '.$this->order->product_name,
        );
    }

    public function content(): Content
    {
        $days = (int) config('shop.download_link_days', 7);

        return new Content(
            markdown: 'mail.shop-order-delivery',
            with: [
                'order' => $this->order,
                'downloadUrl' => URL::temporarySignedRoute(
                    'shop.download',
                    now()->addDays($days),
                    ['order' => $this->order->id],
                ),
                // Carries a long-lived signed unlock so the personaliser
                // exports clean, watermark-free files for this buyer.
                'personalizerUrl' => url('/shop/customize.html').'?unlock='.urlencode(URL::temporarySignedRoute(
                    'shop.unlocked',
                    now()->addYear(),
                    ['order' => $this->order->id],
                )),
                'linkDays' => $days,
            ],
        );
    }
}
