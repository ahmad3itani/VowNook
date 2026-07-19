import { Head, usePage } from '@inertiajs/react';
import { Check, Copy, ExternalLink, QrCode } from 'lucide-react';
import { QRCodeSVG } from 'qrcode.react';
import { useState } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

type ShareLink = {
    key: string;
    title: string;
    description: string;
    path: string;
};

function downloadQr(elementId: string, filename: string) {
    const svg = document.getElementById(elementId);

    if (!(svg instanceof SVGSVGElement)) {
        return;
    }

    const serialized = new XMLSerializer().serializeToString(svg);
    const blob = new Blob([serialized], {
        type: 'image/svg+xml;charset=utf-8',
    });
    const url = URL.createObjectURL(blob);

    const anchor = document.createElement('a');
    anchor.href = url;
    anchor.download = filename;
    anchor.click();

    URL.revokeObjectURL(url);
}

function LinkCard({ link, url }: { link: ShareLink; url: string }) {
    const [copied, setCopied] = useState(false);
    const qrId = `qr-${link.key}`;

    function copy() {
        navigator.clipboard.writeText(url).then(() => {
            setCopied(true);
            toast.success('Link copied to clipboard');
            setTimeout(() => setCopied(false), 2000);
        });
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle>{link.title}</CardTitle>
                <CardDescription>{link.description}</CardDescription>
            </CardHeader>
            <CardContent className="flex flex-col gap-6 sm:flex-row sm:items-center">
                <div className="flex shrink-0 items-center justify-center rounded-xl border bg-white p-3">
                    <QRCodeSVG
                        id={qrId}
                        value={url}
                        size={140}
                        level="M"
                        marginSize={1}
                    />
                </div>

                <div className="flex min-w-0 flex-1 flex-col gap-3">
                    <code className="truncate rounded-md bg-muted px-3 py-2 text-sm text-muted-foreground">
                        {url}
                    </code>
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" size="sm" onClick={copy}>
                            {copied ? (
                                <Check className="text-[#1b4638]" />
                            ) : (
                                <Copy />
                            )}
                            {copied ? 'Copied' : 'Copy link'}
                        </Button>
                        <Button variant="outline" size="sm" asChild>
                            <a
                                href={url}
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <ExternalLink />
                                Open
                            </a>
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() =>
                                downloadQr(qrId, `${link.key}-qr.svg`)
                            }
                        >
                            <QrCode />
                            Download QR
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}

export default function ShareIndex() {
    const { wedding } = usePage().props;
    const active = wedding.active;

    if (!active) {
        return (
            <>
                <Head title="Share" />
                <div className="p-8 text-muted-foreground">
                    No active wedding selected.
                </div>
            </>
        );
    }

    const origin = typeof window !== 'undefined' ? window.location.origin : '';

    const links: ShareLink[] = [
        {
            key: 'website',
            title: 'Wedding website',
            description:
                'Your public front page — story, details, and links to everything below.',
            path: `/w/${active.slug}`,
        },
        {
            key: 'rsvp',
            title: 'RSVP invitation',
            description:
                'Share with your guests so they can reply, choose a meal, and add dietary notes.',
            path: `/w/${active.slug}/rsvp`,
        },
        {
            key: 'seats',
            title: 'Seat finder',
            description:
                'Print this QR code for the venue — guests scan it to find their table.',
            path: `/w/${active.slug}/seats`,
        },
    ];

    return (
        <>
            <Head title="Share" />
            <div className="flex flex-col gap-6 p-4">
                <div>
                    <h1 className="text-2xl font-semibold">
                        Share your wedding
                    </h1>
                    <p className="text-muted-foreground">
                        Public links and printable QR codes for {active.name}.
                    </p>
                </div>

                <div className="grid gap-4">
                    {links.map((link) => (
                        <LinkCard
                            key={link.key}
                            link={link}
                            url={`${origin}${link.path}`}
                        />
                    ))}
                </div>
            </div>
        </>
    );
}

ShareIndex.layout = {
    breadcrumbs: [
        {
            title: 'Share',
            href: '/share',
        },
    ],
};
