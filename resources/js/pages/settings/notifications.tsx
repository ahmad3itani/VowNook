import { Head, useForm } from '@inertiajs/react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';

type PageProps = {
    categories: Record<string, string>;
    preferences: Record<string, boolean>;
};

export default function NotificationSettings({ categories, preferences }: PageProps) {
    const form = useForm<{ preferences: Record<string, boolean> }>({ preferences });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.put('/settings/notifications', {
            preserveScroll: true,
            onSuccess: () => toast.success('Email preferences saved.'),
        });
    }

    function toggle(key: string, value: boolean) {
        form.setData('preferences', { ...form.data.preferences, [key]: value });
    }

    return (
        <>
            <Head title="Notification settings" />
            <h1 className="sr-only">Notification settings</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Email notifications"
                    description="Choose which non-essential emails you'd like to receive. Transactional emails (quotes, RSVPs, security) are always sent."
                />

                <form onSubmit={submit} className="space-y-6">
                    <div className="flex flex-col gap-4">
                        {Object.entries(categories).map(([key, label]) => (
                            <label key={key} className="flex items-start gap-3">
                                <Checkbox
                                    checked={form.data.preferences[key] ?? true}
                                    onCheckedChange={(v) => toggle(key, v === true)}
                                    className="mt-0.5"
                                />
                                <span className="text-sm">{label}</span>
                            </label>
                        ))}
                    </div>

                    <div className="flex items-center gap-4">
                        <Button disabled={form.processing}>Save preferences</Button>
                    </div>
                </form>
            </div>
        </>
    );
}

NotificationSettings.layout = {
    breadcrumbs: [{ title: 'Notification settings', href: '/settings/notifications' }],
};
