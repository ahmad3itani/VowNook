import { Head, Link, router } from '@inertiajs/react';
import { CalendarHeart, Check, Eye, Pencil } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';

type Section = { value: string; label: string };
type Invitation = {
    token: string;
    email: string;
    role: string;
    role_label: string;
    wedding_name: string | null;
    inviter_name: string | null;
    access: Record<string, string>;
    acceptable: boolean;
    accepted: boolean;
};

type PageProps = {
    invitation: Invitation | null;
    sections: Section[];
    auth_email: string | null;
    email_matches: boolean;
};

function Shell({ children }: { children: React.ReactNode }) {
    return (
        <div className="flex min-h-screen flex-col items-center justify-center bg-[#faf6ef] p-6 text-[#191613]">
            <Link href="/" className="mb-8 font-serif text-lg tracking-wide">
                VowNook <span className="text-[#8a651c]">Atelier</span>
            </Link>
            <div className="w-full max-w-md rounded-2xl border border-[#e7ddcb] bg-white p-8 shadow-atelier-lg">
                {children}
            </div>
        </div>
    );
}

function Notice({ title, body }: { title: string; body: string }) {
    return (
        <Shell>
            <h1 className="font-serif text-2xl">{title}</h1>
            <p className="mt-2 text-sm text-[#52493d]">{body}</p>
            <Button asChild className="mt-6">
                <Link href="/">Go home</Link>
            </Button>
        </Shell>
    );
}

export default function InvitationAccept({ invitation, sections, auth_email, email_matches }: PageProps) {
    const [busy, setBusy] = useState(false);

    if (!invitation) {
        return <Notice title="Invitation not found" body="This link may be incorrect or the invitation was cancelled." />;
    }
    if (invitation.accepted) {
        return <Notice title="Already accepted" body="You've already joined this wedding. Sign in to open it from your dashboard." />;
    }
    if (!invitation.acceptable) {
        return <Notice title="Invitation expired" body="This invitation is no longer valid. Ask the couple to send a new one." />;
    }

    const granted = sections.filter((s) => invitation.access[s.value] && invitation.access[s.value] !== 'none');

    function accept() {
        setBusy(true);
        router.post(`/invitations/${invitation!.token}/accept`, {}, { onError: () => setBusy(false) });
    }

    return (
        <Shell>
            <div className="flex flex-col items-center text-center">
                <span className="flex size-12 items-center justify-center rounded-full bg-[#fed488]/40 text-[#8a651c]">
                    <CalendarHeart className="size-6" />
                </span>
                <h1 className="mt-4 font-serif text-2xl">You're invited to help plan</h1>
                <p className="mt-1 text-lg font-medium">{invitation.wedding_name}</p>
                <p className="mt-2 text-sm text-[#52493d]">
                    {invitation.inviter_name ? `${invitation.inviter_name} invited ` : 'You were invited as '}
                    <strong>{invitation.email}</strong> to join as a <strong>{invitation.role_label}</strong>.
                </p>
            </div>

            {/* What they can access */}
            <div className="mt-6">
                <p className="mb-2 text-xs font-medium uppercase tracking-wide text-[#8a651c]">Your access</p>
                {granted.length === 0 ? (
                    <p className="text-sm text-[#52493d]">View-only access to shared sections.</p>
                ) : (
                    <div className="flex flex-wrap gap-1.5">
                        {granted.map((s) => {
                            const edit = invitation.access[s.value] === 'write';
                            return (
                                <span
                                    key={s.value}
                                    className="inline-flex items-center gap-1 rounded-full border border-[#e7ddcb] bg-[#faf6ef] px-2.5 py-1 text-xs"
                                >
                                    {edit ? <Pencil className="size-3 text-[#8a651c]" /> : <Eye className="size-3 text-[#52493d]" />}
                                    {s.label}
                                </span>
                            );
                        })}
                    </div>
                )}
            </div>

            <div className="mt-8">
                {email_matches ? (
                    <Button onClick={accept} disabled={busy} className="w-full">
                        {busy ? <Spinner /> : <Check className="size-4" />}
                        Accept invitation
                    </Button>
                ) : auth_email ? (
                    <div className="text-center text-sm text-[#52493d]">
                        <p>
                            This invitation was sent to <strong>{invitation.email}</strong>, but you're signed in as{' '}
                            <strong>{auth_email}</strong>.
                        </p>
                        <Button asChild variant="outline" className="mt-4">
                            <Link href="/logout" method="post" as="button">Sign out and switch accounts</Link>
                        </Button>
                    </div>
                ) : (
                    <div className="flex flex-col gap-2">
                        <Button asChild className="w-full">
                            <Link href={`/register?email=${encodeURIComponent(invitation.email)}`}>Create an account to accept</Link>
                        </Button>
                        <Button asChild variant="outline" className="w-full">
                            <Link href="/login">I already have an account</Link>
                        </Button>
                        <p className="mt-1 text-center text-xs text-[#52493d]">
                            Use <strong>{invitation.email}</strong> so we can match your invitation.
                        </p>
                    </div>
                )}
            </div>
        </Shell>
    );
}
