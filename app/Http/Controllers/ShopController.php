<?php

namespace App\Http\Controllers;

use App\Models\ShopOrder;
use App\Support\Payments\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * The VowNook Shop (/shop) — a static storefront in public/shop with a hosted
 * Stripe Checkout behind it. The storefront POSTs {product} to /api/shop/checkout
 * and redirects to the returned URL; the shared Stripe webhook fulfils the order
 * and emails a signed download link served by download() below.
 */
class ShopController extends Controller
{
    public function __construct(protected StripeService $stripe) {}

    /** Serve the static storefront for the clean /shop URL. */
    public function index(): BinaryFileResponse
    {
        return response()->file(public_path('shop/index.html'));
    }

    /** Serve a static product page for its clean /shop/p/{slug} URL. */
    public function product(string $slug): BinaryFileResponse
    {
        $known = collect(config('shop.products'))->pluck('slug')->all();
        abort_unless(in_array($slug, $known, true), 404);

        return response()->file(public_path("shop/p/{$slug}.html"));
    }

    /**
     * Confirm a purchase for the personaliser (signed link in the delivery
     * email) so it can export clean, watermark-free files.
     */
    public function unlocked(ShopOrder $order): JsonResponse
    {
        abort_unless($order->isFulfilled(), 403);

        return response()->json(['ok' => true, 'product' => $order->product_key]);
    }

    /** Create a pending order + hosted Checkout session; returns {url}. */
    public function checkout(Request $request): JsonResponse
    {
        $key = (string) $request->input('product', '');
        $product = config("shop.products.{$key}");

        if ($product === null) {
            return response()->json(['error' => 'Unknown product.'], 422);
        }

        if (! $this->stripe->isConfigured()) {
            return response()->json(['error' => 'Payments are not configured yet.'], 503);
        }

        $order = ShopOrder::create([
            'product_key' => $key,
            'product_name' => $product['name'],
            'amount_cents' => $product['amount_cents'],
            'currency' => 'cad',
            'status' => 'pending',
        ]);

        try {
            $url = $this->stripe->shopCheckout(
                $order,
                url('/shop/success.html').'?session_id={CHECKOUT_SESSION_ID}',
                url('/shop/'),
            );
        } catch (Throwable $e) {
            report($e);

            return response()->json(['error' => 'Checkout is not available right now. Please try again.'], 500);
        }

        return response()->json(['url' => $url]);
    }

    /** Stream the purchased ZIP — signed URL only, fulfilled orders only. */
    public function download(ShopOrder $order): StreamedResponse
    {
        abort_unless($order->isFulfilled(), 403, 'This order has not been completed.');

        $file = $order->product()['file'] ?? null;
        abort_if($file === null, 404);
        abort_unless(Storage::exists($file), 404, 'The files are being prepared — please contact hello@vownook.com.');

        return Storage::response($file, basename($file), [
            'Content-Type' => 'application/zip',
        ]);
    }
}
