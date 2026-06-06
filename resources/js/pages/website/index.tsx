import { Head, useForm } from '@inertiajs/react';
import { ExternalLink, Globe } from 'lucide-react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { usePermissions } from '@/hooks/use-permissions';

type Website = {
    is_published: boolean;
    headline: string | null;
    welcome_message: string | null;
    our_story: string | null;
    venue_name: string | null;
    venue_address: string | null;
    ceremony_time: string | null;
    dress_code: string | null;
    hero_image_url: string | null;
};

type PageProps = {
    website: Website;
    public_url: string;
};

type WebsiteFormData = {
    is_published: boolean;
    headline: string;
    welcome_message: string;
    our_story: string;
    venue_name: string;
    venue_address: string;
    ceremony_time: string;
    dress_code: string;
    hero_image_url: string;
};

export default function WebsiteIndex({ website, public_url }: PageProps) {
    const { canWrite } = usePermissions();
    const writable = canWrite('website');

    const form = useForm<WebsiteFormData>({
        is_published: website.is_published,
        headline: website.headline ?? '',
        welcome_message: website.welcome_message ?? '',
        our_story: website.our_story ?? '',
        venue_name: website.venue_name ?? '',
        venue_address: website.venue_address ?? '',
        ceremony_time: website.ceremony_time ?? '',
        dress_code: website.dress_code ?? '',
        hero_image_url: website.hero_image_url ?? '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.put('/website', {
            preserveScroll: true,
            onSuccess: () => toast.success('Website saved.'),
        });
    }

    return (
        <>
            <Head title="Website" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Wedding website"
                        description="Craft the public page your guests see at your wedding's link."
                    />
                    <Button variant="outline" asChild>
                        <a
                            href={public_url}
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <ExternalLink className="size-4" />
                            View site
                        </a>
                    </Button>
                </div>

                <form onSubmit={submit} className="flex flex-col gap-6">
                    <Card>
                        <CardContent className="flex items-center gap-3">
                            <Checkbox
                                id="is_published"
                                checked={form.data.is_published}
                                onCheckedChange={(v) =>
                                    form.setData('is_published', v === true)
                                }
                                disabled={!writable}
                            />
                            <div>
                                <Label
                                    htmlFor="is_published"
                                    className="flex items-center gap-2"
                                >
                                    <Globe className="size-4" />
                                    Publish website
                                </Label>
                                <p className="text-sm text-muted-foreground">
                                    When off, visitors see a simple page with an
                                    RSVP link only.
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Welcome</CardTitle>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-4">
                            <Field
                                label="Headline"
                                error={form.errors.headline}
                            >
                                <Input
                                    value={form.data.headline}
                                    onChange={(e) =>
                                        form.setData('headline', e.target.value)
                                    }
                                    placeholder="We're getting married!"
                                    disabled={!writable}
                                />
                            </Field>
                            <Field
                                label="Welcome message"
                                error={form.errors.welcome_message}
                            >
                                <Textarea
                                    value={form.data.welcome_message}
                                    onChange={(e) =>
                                        form.setData(
                                            'welcome_message',
                                            e.target.value,
                                        )
                                    }
                                    disabled={!writable}
                                />
                            </Field>
                            <Field
                                label="Hero image URL"
                                error={form.errors.hero_image_url}
                            >
                                <Input
                                    value={form.data.hero_image_url}
                                    onChange={(e) =>
                                        form.setData(
                                            'hero_image_url',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="https://…"
                                    disabled={!writable}
                                />
                            </Field>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Our story</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Field
                                label="Tell your story"
                                error={form.errors.our_story}
                            >
                                <Textarea
                                    value={form.data.our_story}
                                    onChange={(e) =>
                                        form.setData(
                                            'our_story',
                                            e.target.value,
                                        )
                                    }
                                    rows={6}
                                    disabled={!writable}
                                />
                            </Field>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Details</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4 sm:grid-cols-2">
                            <Field
                                label="Venue name"
                                error={form.errors.venue_name}
                            >
                                <Input
                                    value={form.data.venue_name}
                                    onChange={(e) =>
                                        form.setData(
                                            'venue_name',
                                            e.target.value,
                                        )
                                    }
                                    disabled={!writable}
                                />
                            </Field>
                            <Field
                                label="Venue address"
                                error={form.errors.venue_address}
                            >
                                <Input
                                    value={form.data.venue_address}
                                    onChange={(e) =>
                                        form.setData(
                                            'venue_address',
                                            e.target.value,
                                        )
                                    }
                                    disabled={!writable}
                                />
                            </Field>
                            <Field
                                label="Ceremony time"
                                error={form.errors.ceremony_time}
                            >
                                <Input
                                    value={form.data.ceremony_time}
                                    onChange={(e) =>
                                        form.setData(
                                            'ceremony_time',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="4:00 PM"
                                    disabled={!writable}
                                />
                            </Field>
                            <Field
                                label="Dress code"
                                error={form.errors.dress_code}
                            >
                                <Input
                                    value={form.data.dress_code}
                                    onChange={(e) =>
                                        form.setData(
                                            'dress_code',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="Garden formal"
                                    disabled={!writable}
                                />
                            </Field>
                        </CardContent>
                    </Card>

                    {writable && (
                        <div className="flex justify-end">
                            <Button type="submit" disabled={form.processing}>
                                {form.processing && <Spinner />}
                                Save website
                            </Button>
                        </div>
                    )}
                </form>
            </div>
        </>
    );
}

function Field({
    label,
    error,
    children,
}: {
    label: string;
    error?: string;
    children: React.ReactNode;
}) {
    return (
        <div className="grid gap-2">
            <Label>{label}</Label>
            {children}
            <InputError message={error} />
        </div>
    );
}

WebsiteIndex.layout = {
    breadcrumbs: [{ title: 'Website', href: '/website' }],
};
