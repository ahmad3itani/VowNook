import { useForm } from '@inertiajs/react';
import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeft,
    BadgeCheck,
    CalendarCheck,
    CheckCircle2,
    CreditCard,
    Send,
    Star,
    Store,
    Tag,
    X,
} from 'lucide-react';
import type { InquiryBooking } from '@/types/inquiry';
import { useState } from 'react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';
import { formatMoney } from '@/lib/format';
import type { CoupleInquiry } from '@/types/inquiry';

type PageProps = {
    inquiry: CoupleInquiry;
};

const STATUS_VARIANT: Record<string, 'default' | 'secondary' | 'outline' | 'destructive'> = {
    requested: 'secondary',
    offered: 'default',
    accepted: 'default',
    declined: 'outline',
    closed: 'outline',
};

const BOOKING_STATUS_VARIANT: Record<string, 'default' | 'secondary' | 'outline'> = {
    pending_payment: 'secondary',
    deposit_paid: 'default',
    paid_in_full: 'default',
    completed: 'default',
};

function BookingPayment({ booking }: { booking: InquiryBooking }) {
    const [busy, setBusy] = useState(false);

    if (!booking.payments_configured) return null;

    const paid = booking.amount_paid_cents ?? 0;
    const depositDue = booking.deposit_due_cents ?? 0;
    const balanceDue = booking.balance_due_cents ?? 0;

    if (booking.status === 'paid_in_full' || booking.status === 'completed') {
        return (
            <div className="flex items-center justify-center gap-1.5 rounded-md bg-[#1b4638]/10 py-2 text-xs font-medium text-[#1b4638]">
                <BadgeCheck className="size-4" /> Paid in full
            </div>
        );
    }

    if (!booking.vendor_can_receive) {
        return <p className="text-center text-xs text-muted-foreground">This vendor is finishing their payment setup.</p>;
    }

    const stage = booking.status === 'pending_payment' && depositDue > 0
        ? { type: 'deposit', amount: depositDue, label: 'Pay deposit' }
        : booking.status === 'deposit_paid' && balanceDue > 0
            ? { type: 'balance', amount: balanceDue, label: 'Pay balance' }
            : null;

    if (!stage) return null;

    function pay() {
        setBusy(true);
        router.post(`/bookings/${booking.id}/checkout/${stage!.type}`, {}, {
            onError: () => { toast.error('Could not start checkout. Please try again.'); setBusy(false); },
        });
    }

    return (
        <div className="space-y-1.5">
            {paid > 0 && (
                <div className="flex items-center justify-between text-xs text-muted-foreground">
                    <span>Paid so far</span>
                    <span>{formatMoney(paid)}</span>
                </div>
            )}
            <Button onClick={pay} disabled={busy} className="w-full">
                <CreditCard className="size-4" /> {stage.label} · {formatMoney(stage.amount)}
            </Button>
            <p className="text-center text-[11px] text-muted-foreground">Secure checkout via Stripe.</p>
        </div>
    );
}

function ReviewStars({ rating }: { rating: number }) {
    return (
        <div className="flex">
            {[1, 2, 3, 4, 5].map((i) => (
                <Star
                    key={i}
                    className={`size-4 ${i <= rating ? 'fill-amber-400 text-amber-400' : 'text-muted-foreground/30'}`}
                />
            ))}
        </div>
    );
}

function RateVendorCard({ bookingId }: { bookingId: number }) {
    const [rating, setRating] = useState(0);
    const [hovered, setHovered] = useState(0);
    const [body, setBody] = useState('');
    const [submitting, setSubmitting] = useState(false);

    function submit(e: React.FormEvent) {
        e.preventDefault();
        if (!rating) return;
        setSubmitting(true);
        router.post(
            '/reviews',
            { booking_id: bookingId, rating, body } as unknown as Record<string, string>,
            {
                preserveScroll: true,
                onSuccess: () => toast.success('Thanks — your review has been posted.'),
                onError: () => toast.error('Could not submit your review. Please try again.'),
                onFinish: () => setSubmitting(false),
            },
        );
    }

    return (
        <Card>
            <CardHeader className="pb-2">
                <CardTitle className="text-sm">Rate this vendor</CardTitle>
            </CardHeader>
            <CardContent>
                <form onSubmit={submit} className="space-y-3">
                    <div className="flex gap-1">
                        {[1, 2, 3, 4, 5].map((i) => (
                            <button
                                key={i}
                                type="button"
                                aria-label={`${i} star${i > 1 ? 's' : ''}`}
                                onClick={() => setRating(i)}
                                onMouseEnter={() => setHovered(i)}
                                onMouseLeave={() => setHovered(0)}
                            >
                                <Star
                                    className={`size-6 transition-colors ${
                                        i <= (hovered || rating)
                                            ? 'fill-amber-400 text-amber-400'
                                            : 'text-muted-foreground/30'
                                    }`}
                                />
                            </button>
                        ))}
                    </div>
                    <Textarea
                        value={body}
                        onChange={(e) => setBody(e.target.value)}
                        rows={3}
                        placeholder="How was your experience? (optional)"
                        className="text-sm"
                    />
                    <Button type="submit" size="sm" className="w-full bg-[#1b4638] hover:bg-[#123025]" disabled={!rating || submitting}>
                        Submit review
                    </Button>
                </form>
            </CardContent>
        </Card>
    );
}

export default function InquiryShow({ inquiry }: PageProps) {
    const { data, setData, post, processing, reset } = useForm({ body: '' });

    function sendMessage(e: React.FormEvent) {
        e.preventDefault();
        if (!data.body.trim()) return;
        post(`/inquiries/${inquiry.id}/messages`, {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    }

    function acceptOffer() {
        if (!confirm('Accept this offer? A booking will be created in your planning workspace.')) return;
        router.post(`/vendors/quotes/${inquiry.id}/accept`, {}, {
            preserveScroll: false,
            onError: () => toast.error('Something went wrong. Please try again.'),
        });
    }

    function declineOffer() {
        if (!confirm('Decline this offer?')) return;
        router.post(`/vendors/quotes/${inquiry.id}/decline`, {}, {
            preserveScroll: false,
            onError: () => toast.error('Something went wrong. Please try again.'),
        });
    }

    const canAct = inquiry.status === 'offered' && inquiry.offer?.status === 'sent';
    const isOpen = inquiry.status === 'requested' || inquiry.status === 'offered';

    return (
        <>
            <Head title={`Inquiry — ${inquiry.vendor.business_name}`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-center gap-3">
                    <Link
                        href="/vendors/quotes"
                        className="flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft className="size-4" /> My quotes
                    </Link>
                </div>

                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="flex items-center gap-3">
                        {inquiry.vendor.logo_url ? (
                            <img
                                src={inquiry.vendor.logo_url}
                                alt={inquiry.vendor.business_name}
                                className="size-10 rounded-lg object-cover"
                            />
                        ) : (
                            <div className="flex size-10 items-center justify-center rounded-lg bg-muted">
                                <Store className="size-5 text-muted-foreground" />
                            </div>
                        )}
                        <div>
                            <Heading
                                title={inquiry.vendor.business_name}
                                description={inquiry.vendor.category_label ?? ''}
                            />
                        </div>
                    </div>
                    <Badge variant={STATUS_VARIANT[inquiry.status] ?? 'outline'}>
                        {inquiry.status_label}
                    </Badge>
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    {/* ── Left: messages + compose ── */}
                    <div className="flex flex-col gap-4 lg:col-span-2">
                        {/* Original inquiry message */}
                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm text-muted-foreground">Your inquiry</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-sm leading-relaxed">{inquiry.message}</p>
                                {(inquiry.event_date || inquiry.guest_count || inquiry.budget_cents) && (
                                    <div className="mt-3 flex flex-wrap gap-3 text-xs text-muted-foreground">
                                        {inquiry.event_date && <span>📅 {inquiry.event_date}</span>}
                                        {inquiry.guest_count && <span>👥 {inquiry.guest_count} guests</span>}
                                        {inquiry.budget_cents && <span>💰 Budget: {formatMoney(inquiry.budget_cents)}</span>}
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Message thread */}
                        {inquiry.messages.length > 0 && (
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-sm">Messages</CardTitle>
                                </CardHeader>
                                <CardContent className="flex flex-col gap-3">
                                    {inquiry.messages.map((m) => (
                                        <div
                                            key={m.id}
                                            className={`flex flex-col gap-1 ${m.is_mine ? 'items-end' : 'items-start'}`}
                                        >
                                            <div
                                                className={`max-w-[85%] rounded-xl px-3 py-2 text-sm ${
                                                    m.is_mine
                                                        ? 'bg-[#1b4638] text-white'
                                                        : 'bg-muted text-foreground'
                                                }`}
                                            >
                                                {m.body}
                                            </div>
                                            <span className="text-xs text-muted-foreground">
                                                {m.is_mine ? 'You' : m.sender_name} · {m.created_at}
                                            </span>
                                        </div>
                                    ))}
                                </CardContent>
                            </Card>
                        )}

                        {/* Compose */}
                        {isOpen && (
                            <form onSubmit={sendMessage}>
                                <Card>
                                    <CardContent className="pt-4">
                                        <Textarea
                                            value={data.body}
                                            onChange={(e) => setData('body', e.target.value)}
                                            placeholder="Send a message to the vendor…"
                                            rows={3}
                                        />
                                        <div className="mt-3 flex justify-end">
                                            <Button type="submit" size="sm" disabled={processing || !data.body.trim()}>
                                                <Send className="mr-1.5 size-4" />
                                                Send
                                            </Button>
                                        </div>
                                    </CardContent>
                                </Card>
                            </form>
                        )}
                    </div>

                    {/* ── Right: offer + booking ── */}
                    <div className="flex flex-col gap-4">
                        {/* Booking confirmation (after accept) */}
                        {inquiry.booking && (
                            <Card className="border-[#1b4638]/30 bg-[#1b4638]/5">
                                <CardHeader className="pb-2">
                                    <CardTitle className="flex items-center gap-2 text-sm text-[#1b4638]">
                                        <CalendarCheck className="size-4" />
                                        Booking confirmed
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-2 text-sm">
                                    <div className="flex items-center justify-between">
                                        <span className="text-muted-foreground">Total</span>
                                        <span className="font-semibold">{formatMoney(inquiry.booking.total_cents)}</span>
                                    </div>
                                    {inquiry.booking.deposit_cents > 0 && (
                                        <div className="flex items-center justify-between">
                                            <span className="text-muted-foreground">Deposit</span>
                                            <span>{formatMoney(inquiry.booking.deposit_cents)}</span>
                                        </div>
                                    )}
                                    <Badge variant={BOOKING_STATUS_VARIANT[inquiry.booking.status] ?? 'outline'} className="w-full justify-center">
                                        {inquiry.booking.status_label}
                                    </Badge>

                                    <BookingPayment booking={inquiry.booking} />

                                    <Link
                                        href="/vendors"
                                        className="mt-1 block text-center text-xs text-[#1b4638] underline underline-offset-2"
                                    >
                                        View in vendor workspace →
                                    </Link>
                                </CardContent>
                            </Card>
                        )}

                        {/* Review — read-only if it exists, otherwise the rate form. */}
                        {inquiry.booking && inquiry.review && (
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-sm">Your review</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-2 text-sm">
                                    <ReviewStars rating={inquiry.review.rating} />
                                    {inquiry.review.body && (
                                        <p className="text-muted-foreground">{inquiry.review.body}</p>
                                    )}
                                    {inquiry.review.vendor_response && (
                                        <div className="ml-3 rounded-lg border-l-2 border-[#1b4638]/40 bg-muted/50 p-2.5">
                                            <p className="text-xs font-semibold text-[#1b4638]">Vendor response</p>
                                            <p className="mt-0.5 text-xs text-muted-foreground">{inquiry.review.vendor_response}</p>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        )}
                        {inquiry.booking && !inquiry.review && (
                            <RateVendorCard bookingId={inquiry.booking.id} />
                        )}

                        {/* Offer card */}
                        {inquiry.offer && (
                            <Card className={canAct ? 'border-[#1b4638]/40' : ''}>
                                <CardHeader className="pb-2">
                                    <div className="flex items-center justify-between">
                                        <CardTitle className="text-sm">Vendor offer</CardTitle>
                                        <Badge variant={inquiry.offer.status === 'sent' ? 'default' : 'secondary'}>
                                            {inquiry.offer.status_label}
                                        </Badge>
                                    </div>
                                </CardHeader>
                                <CardContent className="space-y-3 text-sm">
                                    <div className="flex items-center justify-between font-semibold">
                                        <span>Total</span>
                                        <span className="text-[#1b4638]">{formatMoney(inquiry.offer.total_cents)}</span>
                                    </div>

                                    {inquiry.offer.deposit_cents > 0 && (
                                        <div className="flex items-center justify-between text-muted-foreground">
                                            <span>Deposit required</span>
                                            <span>{formatMoney(inquiry.offer.deposit_cents)}</span>
                                        </div>
                                    )}

                                    {inquiry.offer.line_items.length > 0 && (
                                        <div className="divide-y rounded-lg border text-xs">
                                            {inquiry.offer.line_items.map((li, i) => (
                                                <div key={i} className="flex items-center justify-between px-3 py-2">
                                                    <span>{li.name}{li.qty && li.qty > 1 ? ` ×${li.qty}` : ''}</span>
                                                    <span>{formatMoney(li.amount_cents)}</span>
                                                </div>
                                            ))}
                                        </div>
                                    )}

                                    {inquiry.offer.terms && (
                                        <p className="rounded-md bg-muted p-2 text-xs text-muted-foreground">
                                            {inquiry.offer.terms}
                                        </p>
                                    )}

                                    {inquiry.offer.valid_until && (
                                        <p className="text-xs text-muted-foreground">
                                            Valid until {inquiry.offer.valid_until}
                                        </p>
                                    )}

                                    {canAct && (
                                        <div className="flex flex-col gap-2 pt-2">
                                            <Button className="w-full bg-[#1b4638] hover:bg-[#123025]" onClick={acceptOffer}>
                                                <CheckCircle2 className="mr-1.5 size-4" />
                                                Accept offer
                                            </Button>
                                            <Button variant="outline" className="w-full text-destructive hover:bg-destructive/10" onClick={declineOffer}>
                                                <X className="mr-1.5 size-4" />
                                                Decline
                                            </Button>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        )}

                        {/* Inquiry details */}
                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm">Details</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-2 text-sm text-muted-foreground">
                                {inquiry.service && (
                                    <div className="flex items-center gap-1.5">
                                        <Tag className="size-3.5" />
                                        <span>{inquiry.service.name}</span>
                                    </div>
                                )}
                                <Link
                                    href={`/marketplace/${inquiry.vendor.slug}`}
                                    className="flex items-center gap-1.5 text-[#1b4638] hover:underline"
                                >
                                    <Store className="size-3.5" />
                                    View vendor profile
                                </Link>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </>
    );
}

InquiryShow.layout = {
    breadcrumbs: [
        { title: 'Vendors', href: '/vendors' },
        { title: 'My quotes', href: '/vendors/quotes' },
        { title: 'Quote', href: '#' },
    ],
};
