import { Form, Head } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

type Settings = {
    app_name: string | null;
    brand_primary: string | null;
    brand_tagline: string | null;
};

export default function AdminSettings({
    settings,
    status,
}: {
    settings: Settings;
    status?: string;
}) {
    useEffect(() => {
        if (status === 'settings-updated') {
            toast.success('Settings saved.');
        }
    }, [status]);

    return (
        <>
            <Head title="Admin settings" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Admin settings"
                    description="Branding and platform configuration. Values here override your environment file."
                />

                <Card className="max-w-2xl">
                    <CardHeader>
                        <CardTitle>Branding</CardTitle>
                        <CardDescription>
                            Control how the product presents itself to couples
                            and guests.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Form
                            action="/admin/settings"
                            method="put"
                            options={{ preserveScroll: true }}
                            className="space-y-6"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="app_name">
                                            Application name
                                        </Label>
                                        <Input
                                            id="app_name"
                                            name="app_name"
                                            defaultValue={
                                                settings.app_name ?? ''
                                            }
                                            placeholder="VowNook"
                                        />
                                        <InputError message={errors.app_name} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="brand_primary">
                                            Primary color
                                        </Label>
                                        <div className="flex items-center gap-3">
                                            <Input
                                                id="brand_primary"
                                                name="brand_primary"
                                                defaultValue={
                                                    settings.brand_primary ??
                                                    '#9a7b4f'
                                                }
                                                placeholder="#9a7b4f"
                                                className="max-w-40"
                                            />
                                            <span
                                                className="size-9 rounded-md border"
                                                style={{
                                                    backgroundColor:
                                                        settings.brand_primary ??
                                                        '#9a7b4f',
                                                }}
                                                aria-hidden
                                            />
                                        </div>
                                        <InputError
                                            message={errors.brand_primary}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="brand_tagline">
                                            Tagline
                                        </Label>
                                        <Input
                                            id="brand_tagline"
                                            name="brand_tagline"
                                            defaultValue={
                                                settings.brand_tagline ?? ''
                                            }
                                            placeholder="A wedding, composed."
                                        />
                                        <InputError
                                            message={errors.brand_tagline}
                                        />
                                    </div>

                                    <Button
                                        disabled={processing}
                                        data-test="save-settings"
                                    >
                                        {processing && <Spinner />}
                                        Save changes
                                    </Button>
                                </>
                            )}
                        </Form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

AdminSettings.layout = {
    breadcrumbs: [{ title: 'Admin settings', href: '/admin/settings' }],
};
