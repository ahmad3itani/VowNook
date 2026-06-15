import { Head, router } from '@inertiajs/react';
import { Search, ShieldCheck } from 'lucide-react';
import { useMemo, useState } from 'react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

type Option = { value: string; label: string };
type User = {
    id: number;
    name: string;
    email: string;
    account_type: string;
    plan: string;
    is_admin: boolean;
    weddings_count: number;
    created_at: string | null;
};

type PageProps = { users: User[]; plans: Option[] };

const TYPES = ['all', 'couple', 'planner', 'vendor'];

export default function AdminUsers({ users, plans }: PageProps) {
    const [search, setSearch] = useState('');
    const [type, setType] = useState('all');

    const filtered = useMemo(() => {
        const term = search.trim().toLowerCase();
        return users.filter((u) => {
            if (type !== 'all' && u.account_type !== type) return false;
            if (term && !u.name.toLowerCase().includes(term) && !u.email.toLowerCase().includes(term)) return false;
            return true;
        });
    }, [users, search, type]);

    function changePlan(user: User, plan: string) {
        if (plan === user.plan) return;
        router.put(`/admin/users/${user.id}/plan`, { plan }, {
            preserveScroll: true,
            onSuccess: () => toast.success(`${user.name} moved to ${plan}.`),
            onError: () => toast.error('Could not change plan.'),
        });
    }

    return (
        <>
            <Head title="Users" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading title="Users" description="Every account on the platform — couples, planners, and vendors." />

                <div className="flex flex-wrap items-center gap-3">
                    <div className="relative max-w-sm flex-1">
                        <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Search name or email…" className="pl-9" />
                    </div>
                    <div className="flex items-center gap-1.5 rounded-lg border border-border bg-card p-1">
                        {TYPES.map((t) => (
                            <button key={t} type="button" onClick={() => setType(t)}
                                className={`rounded px-3 py-1 text-xs font-medium capitalize transition-colors ${type === t ? 'bg-[#775a19] text-white' : 'text-muted-foreground hover:bg-muted'}`}>
                                {t}
                            </button>
                        ))}
                    </div>
                </div>

                <Card>
                    <CardContent className="p-0">
                        {filtered.length === 0 ? (
                            <p className="py-12 text-center text-sm text-muted-foreground">No users match.</p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead className="border-b text-left text-muted-foreground">
                                        <tr>
                                            <th className="px-4 py-3 font-medium">Name</th>
                                            <th className="px-4 py-3 font-medium">Type</th>
                                            <th className="px-4 py-3 font-medium">Plan</th>
                                            <th className="px-4 py-3 text-right font-medium">Weddings</th>
                                            <th className="px-4 py-3 font-medium">Joined</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {filtered.map((u) => (
                                            <tr key={u.id} className="border-b last:border-0">
                                                <td className="px-4 py-3">
                                                    <div className="flex items-center gap-2">
                                                        <span className="font-medium">{u.name}</span>
                                                        {u.is_admin && <ShieldCheck className="size-3.5 text-[#775a19]" aria-label="Admin" />}
                                                    </div>
                                                    <div className="text-xs text-muted-foreground">{u.email}</div>
                                                </td>
                                                <td className="px-4 py-3"><Badge variant="outline" className="capitalize">{u.account_type}</Badge></td>
                                                <td className="px-4 py-3">
                                                    <Select value={u.plan} onValueChange={(v) => changePlan(u, v)}>
                                                        <SelectTrigger className="h-8 w-32"><SelectValue /></SelectTrigger>
                                                        <SelectContent>
                                                            {plans.map((p) => <SelectItem key={p.value} value={p.value}>{p.label}</SelectItem>)}
                                                        </SelectContent>
                                                    </Select>
                                                </td>
                                                <td className="px-4 py-3 text-right tabular-nums">{u.weddings_count}</td>
                                                <td className="px-4 py-3 text-muted-foreground">{u.created_at}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

AdminUsers.layout = {
    breadcrumbs: [
        { title: 'Console', href: '/admin/dashboard' },
        { title: 'Users', href: '/admin/users' },
    ],
};
