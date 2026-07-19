import { Head, Link, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

type Actor = { id: number; name: string };
type Log = {
    id: number;
    action: string;
    actor: Actor | null;
    subject_type: string | null;
    subject_id: number | null;
    description: string | null;
    ip: string | null;
    created_at: string | null;
};
type PaginationLink = { url: string | null; label: string; active: boolean };
type Paginator = { data: Log[]; links: PaginationLink[] };

type PageProps = {
    logs: Paginator;
    actions: string[];
    filter: { action: string | null };
};

function fmt(iso: string | null): string {
    if (!iso) return '';
    return new Date(iso).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
}

function actionTone(action: string): string {
    if (action.startsWith('admin.user.suspend')) return 'text-destructive';
    if (action.startsWith('admin.impersonate')) return 'text-violet-700 dark:text-violet-300';
    if (action.startsWith('admin.')) return 'text-[#1b4638]';
    return 'text-foreground';
}

export default function AdminActivity({ logs, actions, filter }: PageProps) {
    function setAction(value: string) {
        router.get('/admin/activity', value === 'all' ? {} : { action: value }, { preserveState: true, preserveScroll: true });
    }

    return (
        <>
            <Head title="Activity" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <Heading title="Activity" description="Every admin action and key account event, newest first." />
                    <Select value={filter.action ?? 'all'} onValueChange={setAction}>
                        <SelectTrigger className="w-56"><SelectValue placeholder="All actions" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All actions</SelectItem>
                            {actions.map((a) => <SelectItem key={a} value={a}>{a}</SelectItem>)}
                        </SelectContent>
                    </Select>
                </div>

                <Card>
                    <CardContent className="p-0">
                        {logs.data.length === 0 ? (
                            <p className="py-12 text-center text-sm text-muted-foreground">No activity yet.</p>
                        ) : (
                            <ul className="divide-y">
                                {logs.data.map((log) => (
                                    <li key={log.id} className="flex flex-wrap items-center justify-between gap-3 px-4 py-3 text-sm">
                                        <div className="flex items-center gap-3">
                                            <code className={`font-mono text-xs font-medium ${actionTone(log.action)}`}>{log.action}</code>
                                            {log.subject_type && (
                                                <Badge variant="outline" className="text-[10px]">
                                                    {log.subject_type}#{log.subject_id}
                                                </Badge>
                                            )}
                                            {log.description && <span className="text-muted-foreground">{log.description}</span>}
                                        </div>
                                        <div className="flex items-center gap-3 text-xs text-muted-foreground">
                                            {log.actor && <span>{log.actor.name}</span>}
                                            {log.ip && <span className="hidden sm:inline">{log.ip}</span>}
                                            <span>{fmt(log.created_at)}</span>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>

                {logs.links.length > 3 && (
                    <div className="flex flex-wrap justify-center gap-1">
                        {logs.links.map((link, i) => (
                            <Link
                                key={i}
                                href={link.url ?? '#'}
                                preserveScroll
                                className={`rounded px-3 py-1 text-xs ${
                                    link.active ? 'bg-[#1b4638] text-white' : link.url ? 'hover:bg-muted' : 'cursor-default text-muted-foreground/50'
                                }`}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

AdminActivity.layout = {
    breadcrumbs: [
        { title: 'Console', href: '/admin/dashboard' },
        { title: 'Activity', href: '/admin/activity' },
    ],
};
