import { Head, Link, router } from '@inertiajs/react';
import {
    BadgeCheck,
    CalendarHeart,
    Clock,
    Eye,
    KeyRound,
    Mail,
    ShieldAlert,
    ShieldCheck,
    Store,
    Ticket,
    TrendingUp,
} from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

type Option = { value: string; label: string };

type Subject = {
    id: number;
    name: string;
    email: string;
    account_type: string;
    plan: string;
    plan_comped_until: string | null;
    is_admin: boolean;
    suspended: boolean;
    suspended_reason: string | null;
    email_verified: boolean;
    last_login_at: string | null;
    last_login_ip: string | null;
    created_at: string | null;
    referral_code: string | null;
    referrals_count: number;
};

type Wedding = { id: number; name: string; slug: string; event_date: string | null; guests_count: number };
type VendorProfile = { id: number; business_name: string; slug: string; status: string; rating_avg: number | null; rating_count: number };
type Stats = { bookings: number; gmv: number; open_inquiries: number; support_tickets: number };
type Activity = { id: number; action: string; actor: string | null; description: string | null; ip: string | null; created_at: string | null };

type PageProps = {
    subject: Subject;
    weddings: Wedding[];
    vendor_profile: VendorProfile | null;
    stats: Stats;
    activity: Activity[];
    plans: Option[];
    can_impersonate: boolean;
};

function fmtDate(iso: string | null): string {
    if (!iso) return '—';
    return new Date(iso).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
}

function fmtDateTime(iso: string | null): string {
    if (!iso) return 'Never';
    return new Date(iso).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
}

const ACTION_LABEL: Record<string, string> = {
    'auth.login': 'Signed in',
    'admin.impersonate.start': 'Admin started viewing as this user',
    'admin.impersonate.stop': 'Admin stopped viewing as this user',
    'admin.user.plan': 'Plan changed',
    'admin.user.comp': 'Plan comped',
    'admin.user.suspend': 'Account suspended',
    'admin.user.unsuspend': 'Account reinstated',
    'admin.user.password_reset': 'Password reset sent',
    'admin.user.resend_verification': 'Verification email resent',
};

export default function AdminUserShow({ subject, weddings, vendor_profile, stats, activity, plans, can_impersonate }: PageProps) {
    const [plan, setPlan] = useState(subject.plan);
    const [compDays, setCompDays] = useState('30');
    const [reason, setReason] = useState('');
    const [busy, setBusy] = useState(false);

    const post = (url: string, data: Record<string, string | number | boolean | null> = {}, msg = 'Done') =>
        router.post(url, data, {
            preserveScroll: true,
            onStart: () => setBusy(true),
            onFinish: () => setBusy(false),
            onSuccess: () => toast.success(msg),
            onError: () => toast.error('Something went wrong.'),
        });

    return (
        <>
            <Head title={subject.name} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading title={subject.name} description={subject.email} />
                    <div className="flex flex-wrap items-center gap-2">
                        <Badge variant="outline" className="capitalize">{subject.account_type}</Badge>
                        {subject.is_admin && (
                            <Badge className="gap-1 bg-[#775a19] hover:bg-[#775a19]"><ShieldCheck className="size-3" /> Admin</Badge>
                        )}
                        {subject.suspended ? (
                            <Badge variant="destructive">Suspended</Badge>
                        ) : (
                            <Badge variant="secondary">Active</Badge>
                        )}
                        {can_impersonate && (
                            <Button size="sm" disabled={busy} onClick={() => post(`/admin/users/${subject.id}/impersonate`, {}, 'Now viewing as this user.')}>
                                <Eye className="size-4" /> View as user
                            </Button>
                        )}
                    </div>
                </div>

                {/* Stat tiles */}
                <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    {[
                        { icon: TrendingUp, label: 'Bookings', value: stats.bookings },
                        { icon: BadgeCheck, label: 'GMV', value: `$${stats.gmv.toLocaleString()}` },
                        { icon: Clock, label: 'Open quotes', value: stats.open_inquiries },
                        { icon: Ticket, label: 'Tickets', value: stats.support_tickets },
                    ].map((s) => (
                        <Card key={s.label}>
                            <CardContent className="flex items-center gap-3 p-4">
                                <s.icon className="size-5 text-[#775a19]" />
                                <div>
                                    <div className="text-lg font-semibold tabular-nums">{s.value}</div>
                                    <div className="text-xs text-muted-foreground">{s.label}</div>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    {/* Left: account + entities */}
                    <div className="flex flex-col gap-6 lg:col-span-2">
                        <Card>
                            <CardHeader><CardTitle className="text-base">Account</CardTitle></CardHeader>
                            <CardContent className="grid gap-y-3 sm:grid-cols-2">
                                <Field label="Joined" value={fmtDate(subject.created_at)} />
                                <Field label="Last sign-in" value={fmtDateTime(subject.last_login_at)} />
                                <Field label="Last IP" value={subject.last_login_ip ?? '—'} />
                                <Field label="Email verified" value={subject.email_verified ? 'Yes' : 'No'} />
                                <Field label="Plan" value={subject.plan} />
                                <Field
                                    label="Comp expires"
                                    value={subject.plan_comped_until ? fmtDate(subject.plan_comped_until) : '—'}
                                />
                                <Field label="Referral code" value={subject.referral_code ?? '—'} />
                                <Field label="Referred signups" value={String(subject.referrals_count)} />
                            </CardContent>
                        </Card>

                        {vendor_profile && (
                            <Card>
                                <CardHeader><CardTitle className="flex items-center gap-2 text-base"><Store className="size-4" /> Vendor profile</CardTitle></CardHeader>
                                <CardContent className="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <div className="font-medium">{vendor_profile.business_name}</div>
                                        <div className="text-xs text-muted-foreground">
                                            {vendor_profile.status} · ★ {vendor_profile.rating_avg ?? '—'} ({vendor_profile.rating_count})
                                        </div>
                                    </div>
                                    <a href={`/marketplace/${vendor_profile.slug}`} target="_blank" rel="noreferrer" className="text-sm font-medium text-[#775a19] hover:underline">
                                        View listing
                                    </a>
                                </CardContent>
                            </Card>
                        )}

                        <Card>
                            <CardHeader><CardTitle className="flex items-center gap-2 text-base"><CalendarHeart className="size-4" /> Weddings ({weddings.length})</CardTitle></CardHeader>
                            <CardContent className="p-0">
                                {weddings.length === 0 ? (
                                    <p className="px-6 py-6 text-sm text-muted-foreground">No weddings owned.</p>
                                ) : (
                                    <ul className="divide-y">
                                        {weddings.map((w) => (
                                            <li key={w.id} className="flex items-center justify-between gap-3 px-6 py-3">
                                                <div>
                                                    <div className="font-medium">{w.name}</div>
                                                    <div className="text-xs text-muted-foreground">{fmtDate(w.event_date)} · {w.guests_count} guests</div>
                                                </div>
                                                <Link href={`/admin/weddings/${w.id}`} className="text-xs font-medium text-[#775a19] hover:underline">Open</Link>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader><CardTitle className="flex items-center gap-2 text-base"><Clock className="size-4" /> Recent activity</CardTitle></CardHeader>
                            <CardContent className="p-0">
                                {activity.length === 0 ? (
                                    <p className="px-6 py-6 text-sm text-muted-foreground">No activity recorded yet.</p>
                                ) : (
                                    <ul className="divide-y">
                                        {activity.map((a) => (
                                            <li key={a.id} className="flex items-start justify-between gap-3 px-6 py-3 text-sm">
                                                <div>
                                                    <div>{ACTION_LABEL[a.action] ?? a.action}</div>
                                                    {a.actor && <div className="text-xs text-muted-foreground">by {a.actor}</div>}
                                                </div>
                                                <div className="shrink-0 text-right text-xs text-muted-foreground">
                                                    {fmtDateTime(a.created_at)}
                                                    {a.ip && <div>{a.ip}</div>}
                                                </div>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    {/* Right: support actions */}
                    <div className="flex flex-col gap-6">
                        <Card>
                            <CardHeader><CardTitle className="text-base">Plan</CardTitle></CardHeader>
                            <CardContent className="flex flex-col gap-3">
                                <div className="flex gap-2">
                                    <Select value={plan} onValueChange={setPlan}>
                                        <SelectTrigger><SelectValue /></SelectTrigger>
                                        <SelectContent>
                                            {plans.map((p) => <SelectItem key={p.value} value={p.value}>{p.label}</SelectItem>)}
                                        </SelectContent>
                                    </Select>
                                    <Button variant="outline" disabled={busy || plan === subject.plan} onClick={() => post(`/admin/users/${subject.id}/plan`, { plan }, 'Plan updated.')}>
                                        Set
                                    </Button>
                                </div>
                                <div className="flex items-end gap-2">
                                    <div className="flex-1">
                                        <Label className="text-xs">Comp for (days)</Label>
                                        <Input type="number" min={1} value={compDays} onChange={(e) => setCompDays(e.target.value)} />
                                    </div>
                                    <Button variant="outline" disabled={busy} onClick={() => post(`/admin/users/${subject.id}/comp`, { plan, days: Number(compDays) }, 'Plan comped.')}>
                                        Comp
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader><CardTitle className="text-base">Support actions</CardTitle></CardHeader>
                            <CardContent className="flex flex-col gap-2">
                                <Button variant="outline" className="justify-start" disabled={busy} onClick={() => post(`/admin/users/${subject.id}/password-reset`, {}, 'Password-reset email sent.')}>
                                    <KeyRound className="size-4" /> Send password reset
                                </Button>
                                {!subject.email_verified && (
                                    <Button variant="outline" className="justify-start" disabled={busy} onClick={() => post(`/admin/users/${subject.id}/resend-verification`, {}, 'Verification email sent.')}>
                                        <Mail className="size-4" /> Resend verification
                                    </Button>
                                )}
                            </CardContent>
                        </Card>

                        {!subject.is_admin && (
                            <Card className="border-destructive/30">
                                <CardHeader><CardTitle className="flex items-center gap-2 text-base text-destructive"><ShieldAlert className="size-4" /> Danger zone</CardTitle></CardHeader>
                                <CardContent className="flex flex-col gap-3">
                                    {subject.suspended ? (
                                        <>
                                            {subject.suspended_reason && <p className="text-xs text-muted-foreground">Reason: {subject.suspended_reason}</p>}
                                            <Button variant="outline" disabled={busy} onClick={() => post(`/admin/users/${subject.id}/unsuspend`, {}, 'Account reinstated.')}>
                                                Reinstate account
                                            </Button>
                                        </>
                                    ) : (
                                        <>
                                            <Input placeholder="Reason (optional)" value={reason} onChange={(e) => setReason(e.target.value)} />
                                            <Button variant="destructive" disabled={busy} onClick={() => post(`/admin/users/${subject.id}/suspend`, { reason }, 'Account suspended.')}>
                                                Suspend account
                                            </Button>
                                        </>
                                    )}
                                </CardContent>
                            </Card>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}

function Field({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <div className="text-xs text-muted-foreground">{label}</div>
            <div className="text-sm font-medium">{value}</div>
        </div>
    );
}

AdminUserShow.layout = {
    breadcrumbs: [
        { title: 'Console', href: '/admin/dashboard' },
        { title: 'Users', href: '/admin/users' },
    ],
};
