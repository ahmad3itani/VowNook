import { useForm } from '@inertiajs/react';
import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeft,
    Plus,
    Send,
    Star,
    Trash2,
    X,
} from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { formatMoney } from '@/lib/format';
import type { VendorInquiry } from '@/types/inquiry';

type LineItem = { name: string; amount_cents: number | ''; qty: number | '' };

type PageProps = {
    inquiry: VendorInquiry;
};

const STATUS_VARIANT: Record<string, 'default' | 'secondary' | 'outline'> = {
    requested: 'secondary',
    offered: 'default',
    accepted: 'default',
    declined: 'outline',
};

export default function VendorInquiryShow({ inquiry }: PageProps) {
    const [showOfferForm, setShowOfferForm] = useState(false);
    const [lineItems, setLineItems] = useState<LineItem[]>([{ name: '', amount_cents: '', qty: '' }]);
    const [reviewResponse, setReviewResponse] = useState('');
    const [respondingToReview, setRespondingToReview] = useState(false);

    const msgForm = useForm({ body: '' });
    const offerForm = useForm({
        total_cents: '' as number | '',
        deposit_cents: '' as number | '',
        terms: '',
        valid_until: '',
    });

    const canSendOffer = inquiry.status === 'requested' || inquiry.status === 'offered';
    const isOpen = inquiry.status === 'requested' || inquiry.status === 'offered';

    function sendMessage(e: React.FormEvent) {
        e.preventDefault();
        if (!msgForm.data.body.trim()) return;
        msgForm.post(`/inquiries/${inquiry.id}/messages`, {
            preserveScroll: true,
            onSuccess: () => msgForm.reset(),
        });
    }

    function addLineItem() {
        setLineItems((prev) => [...prev, { name: '', amount_cents: '', qty: '' }]);
    }

    function removeLineItem(idx: number) {
        setLineItems((prev) => prev.filter((_, i) => i !== idx));
    }

    function updateLineItem(idx: number, field: keyof LineItem, value: string) {
        setLineItems((prev) =>
            prev.map((li, i) => {
                if (i !== idx) return li;
                if (field === 'amount_cents' || field === 'qty') {
                    return { ...li, [field]: value === '' ? '' : Number(value) };
                }
                return { ...li, [field]: value };
            }),
        );
    }

    function sendOffer(e: React.FormEvent) {
        e.preventDefault();
        const validItems = lineItems.filter((li) => li.name && li.amount_cents !== '');
        router.post(
            `/vendor/inquiries/${inquiry.id}/offer`,
            {
                total_cents: offerForm.data.total_cents,
                deposit_cents: offerForm.data.deposit_cents || 0,
                terms: offerForm.data.terms || null,
                valid_until: offerForm.data.valid_until || null,
                line_items: validItems.map((li) => ({
                    name: li.name,
                    amount_cents: Number(li.amount_cents),
                    qty: li.qty === '' ? 1 : Number(li.qty),
                })),
            } as unknown as Record<string, string>,
            {
                preserveScroll: true,
                onSuccess: () => {
                    setShowOfferForm(false);
                    offerForm.reset();
                    setLineItems([{ name: '', amount_cents: '', qty: '' }]);
                },
                onError: () => toast.error('Something went wrong. Please try again.'),
            },
        );
    }

    function respondToReview(e: React.FormEvent) {
        e.preventDefault();
        if (!inquiry.review?.id || !reviewResponse.trim()) return;
        setRespondingToReview(true);
        router.post(
            `/vendor/reviews/${inquiry.review.id}/respond`,
            { response: reviewResponse },
            {
                preserveScroll: true,
                onSuccess: () => toast.success('Response posted.'),
                onError: () => toast.error('Something went wrong. Please try again.'),
                onFinish: () => setRespondingToReview(false),
            },
        );
    }

    function withdrawOffer() {
        if (!confirm('Withdraw this offer?')) return;
        router.delete(`/vendor/inquiries/${inquiry.id}/offer`, {
            preserveScroll: true,
            onError: () => toast.error('Something went wrong. Please try again.'),
        });
    }

    return (
        <>
            <Head title={`Inquiry — ${inquiry.wedding_name ?? 'Wedding'}`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Link
                    href="/vendor/inquiries"
                    className="flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground"
                >
                    <ArrowLeft className="size-4" /> Inquiries
                </Link>

                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title={inquiry.wedding_name ?? 'Wedding'}
                        description={inquiry.event_date ? `Event: ${inquiry.event_date}` : 'No event date given'}
                    />
                    <Badge variant={STATUS_VARIANT[inquiry.status] ?? 'outline'}>
                        {inquiry.status_label}
                    </Badge>
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    {/* ── Messages + compose ── */}
                    <div className="flex flex-col gap-4 lg:col-span-2">
                        {/* Inquiry message */}
                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm text-muted-foreground">Couple's message</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-sm leading-relaxed">{inquiry.message}</p>
                                <div className="mt-3 flex flex-wrap gap-3 text-xs text-muted-foreground">
                                    {inquiry.event_date && <span>📅 {inquiry.event_date}</span>}
                                    {inquiry.guest_count && <span>👥 {inquiry.guest_count} guests</span>}
                                    {inquiry.budget_cents && <span>💰 Budget: {formatMoney(inquiry.budget_cents)}</span>}
                                    {inquiry.service && <span>📦 Service: {inquiry.service.name}</span>}
                                </div>
                            </CardContent>
                        </Card>

                        {/* Thread */}
                        {inquiry.messages.length > 0 && (
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-sm">Messages</CardTitle>
                                </CardHeader>
                                <CardContent className="flex flex-col gap-3">
                                    {inquiry.messages.map((m) => (
                                        <div key={m.id} className={`flex flex-col gap-1 ${m.is_mine ? 'items-end' : 'items-start'}`}>
                                            <div className={`max-w-[85%] rounded-xl px-3 py-2 text-sm ${m.is_mine ? 'bg-[#1b4638] text-white' : 'bg-muted'}`}>
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
                                            value={msgForm.data.body}
                                            onChange={(e) => msgForm.setData('body', e.target.value)}
                                            placeholder="Reply to the couple…"
                                            rows={3}
                                        />
                                        <div className="mt-3 flex justify-end">
                                            <Button type="submit" size="sm" disabled={msgForm.processing || !msgForm.data.body.trim()}>
                                                <Send className="mr-1.5 size-4" /> Reply
                                            </Button>
                                        </div>
                                    </CardContent>
                                </Card>
                            </form>
                        )}
                    </div>

                    {/* ── Offer sidebar ── */}
                    <div className="flex flex-col gap-4">
                        {/* Current offer */}
                        {inquiry.offer && (
                            <Card className={inquiry.offer.status === 'sent' ? 'border-[#1b4638]/40' : ''}>
                                <CardHeader className="pb-2">
                                    <div className="flex items-center justify-between">
                                        <CardTitle className="text-sm">Your offer</CardTitle>
                                        <Badge variant={inquiry.offer.status === 'sent' ? 'default' : 'secondary'}>
                                            {inquiry.offer.status_label}
                                        </Badge>
                                    </div>
                                </CardHeader>
                                <CardContent className="space-y-2 text-sm">
                                    <div className="flex items-center justify-between font-semibold">
                                        <span>Total</span>
                                        <span className="text-[#1b4638]">{formatMoney(inquiry.offer.total_cents)}</span>
                                    </div>
                                    {inquiry.offer.deposit_cents > 0 && (
                                        <div className="flex items-center justify-between text-muted-foreground">
                                            <span>Deposit</span>
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
                                    {inquiry.offer.valid_until && (
                                        <p className="text-xs text-muted-foreground">Valid until {inquiry.offer.valid_until}</p>
                                    )}
                                    {inquiry.offer.status === 'sent' && (
                                        <div className="flex flex-col gap-2 pt-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                className="w-full"
                                                onClick={() => setShowOfferForm(true)}
                                            >
                                                Revise offer
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                className="w-full text-destructive hover:bg-destructive/10"
                                                onClick={withdrawOffer}
                                            >
                                                <X className="mr-1.5 size-4" />
                                                Withdraw
                                            </Button>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        )}

                        {/* Offer builder */}
                        {canSendOffer && (showOfferForm || !inquiry.offer) && (
                            <Card className="border-primary/30">
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-sm text-primary">
                                        {inquiry.offer ? 'Revise offer' : 'Send an offer'}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <form onSubmit={sendOffer} className="space-y-4">
                                        {/* Line items */}
                                        <div>
                                            <Label className="mb-2 block text-xs">Line items</Label>
                                            <div className="space-y-2">
                                                {lineItems.map((li, idx) => (
                                                    <div key={idx} className="flex items-center gap-2">
                                                        <Input
                                                            value={li.name}
                                                            onChange={(e) => updateLineItem(idx, 'name', e.target.value)}
                                                            placeholder="Description"
                                                            className="h-8 flex-1 text-xs"
                                                        />
                                                        <Input
                                                            type="number"
                                                            min={0}
                                                            value={li.amount_cents}
                                                            onChange={(e) => updateLineItem(idx, 'amount_cents', e.target.value)}
                                                            placeholder="Cents"
                                                            className="h-8 w-24 text-xs"
                                                        />
                                                        <Button
                                                            type="button"
                                                            variant="ghost"
                                                            size="icon"
                                                            className="size-8 shrink-0"
                                                            onClick={() => removeLineItem(idx)}
                                                            disabled={lineItems.length === 1}
                                                        >
                                                            <Trash2 className="size-3" />
                                                        </Button>
                                                    </div>
                                                ))}
                                            </div>
                                            <Button type="button" variant="ghost" size="sm" className="mt-2 h-7 text-xs" onClick={addLineItem}>
                                                <Plus className="mr-1 size-3" /> Add line
                                            </Button>
                                        </div>

                                        <div className="grid grid-cols-2 gap-3">
                                            <div>
                                                <Label htmlFor="offer-total" className="text-xs">Total (cents) *</Label>
                                                <Input
                                                    id="offer-total"
                                                    type="number"
                                                    min={1}
                                                    required
                                                    value={offerForm.data.total_cents}
                                                    onChange={(e) => offerForm.setData('total_cents', e.target.value === '' ? '' : Number(e.target.value))}
                                                    className="mt-1 h-8 text-sm"
                                                    placeholder="e.g. 250000"
                                                />
                                                {offerForm.data.total_cents !== '' && Number(offerForm.data.total_cents) > 0 && (
                                                    <p className="mt-0.5 text-xs text-muted-foreground">
                                                        {formatMoney(Number(offerForm.data.total_cents))}
                                                    </p>
                                                )}
                                            </div>
                                            <div>
                                                <Label htmlFor="offer-deposit" className="text-xs">Deposit (cents)</Label>
                                                <Input
                                                    id="offer-deposit"
                                                    type="number"
                                                    min={0}
                                                    value={offerForm.data.deposit_cents}
                                                    onChange={(e) => offerForm.setData('deposit_cents', e.target.value === '' ? '' : Number(e.target.value))}
                                                    className="mt-1 h-8 text-sm"
                                                    placeholder="0"
                                                />
                                            </div>
                                        </div>

                                        <div>
                                            <Label htmlFor="offer-terms" className="text-xs">Terms / notes</Label>
                                            <Textarea
                                                id="offer-terms"
                                                value={offerForm.data.terms}
                                                onChange={(e) => offerForm.setData('terms', e.target.value)}
                                                rows={2}
                                                className="mt-1 text-sm"
                                                placeholder="Any conditions, cancellation policy…"
                                            />
                                        </div>

                                        <div>
                                            <Label htmlFor="offer-valid" className="text-xs">Valid until</Label>
                                            <Input
                                                id="offer-valid"
                                                type="date"
                                                value={offerForm.data.valid_until}
                                                onChange={(e) => offerForm.setData('valid_until', e.target.value)}
                                                className="mt-1 h-8 text-sm"
                                            />
                                        </div>

                                        <div className="flex items-center gap-2">
                                            <Button
                                                type="submit"
                                                size="sm"
                                                className="flex-1 bg-[#1b4638] hover:bg-[#123025]"
                                                disabled={offerForm.processing || !offerForm.data.total_cents}
                                            >
                                                <Send className="mr-1.5 size-4" />
                                                {inquiry.offer ? 'Revise & resend' : 'Send offer'}
                                            </Button>
                                            {showOfferForm && (
                                                <Button type="button" variant="ghost" size="sm" onClick={() => setShowOfferForm(false)}>
                                                    Cancel
                                                </Button>
                                            )}
                                        </div>
                                    </form>
                                </CardContent>
                            </Card>
                        )}

                        {/* Couple's review + vendor response */}
                        {inquiry.review && (
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-sm">Couple's review</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3 text-sm">
                                    <div className="flex">
                                        {[1, 2, 3, 4, 5].map((i) => (
                                            <Star
                                                key={i}
                                                className={`size-4 ${i <= inquiry.review!.rating ? 'fill-amber-400 text-amber-400' : 'text-muted-foreground/30'}`}
                                            />
                                        ))}
                                    </div>
                                    {inquiry.review.body && (
                                        <p className="text-muted-foreground">{inquiry.review.body}</p>
                                    )}
                                    {inquiry.review.vendor_response ? (
                                        <div className="ml-3 rounded-lg border-l-2 border-[#1b4638]/40 bg-muted/50 p-2.5">
                                            <p className="text-xs font-semibold text-[#1b4638]">Your response</p>
                                            <p className="mt-0.5 text-xs text-muted-foreground">{inquiry.review.vendor_response}</p>
                                        </div>
                                    ) : (
                                        <form onSubmit={respondToReview} className="space-y-2">
                                            <Textarea
                                                value={reviewResponse}
                                                onChange={(e) => setReviewResponse(e.target.value)}
                                                rows={3}
                                                placeholder="Respond publicly to this review…"
                                                className="text-sm"
                                            />
                                            <Button
                                                type="submit"
                                                size="sm"
                                                className="w-full"
                                                disabled={respondingToReview || !reviewResponse.trim()}
                                            >
                                                Post response
                                            </Button>
                                        </form>
                                    )}
                                </CardContent>
                            </Card>
                        )}

                        {!canSendOffer && !inquiry.offer && (
                            <Card>
                                <CardContent className="py-6 text-center text-sm text-muted-foreground">
                                    Inquiry is {inquiry.status_label.toLowerCase()}.
                                </CardContent>
                            </Card>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}

VendorInquiryShow.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/vendor' },
        { title: 'Inquiries', href: '/vendor/inquiries' },
        { title: 'Inquiry', href: '#' },
    ],
};
