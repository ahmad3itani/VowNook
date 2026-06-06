import { Form, usePage } from '@inertiajs/react';
import { MailWarning } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { send } from '@/routes/verification';

/**
 * Shown inside the authenticated shell when the current user has not yet
 * verified their email. Offers a one-click resend.
 */
export function VerifyEmailBanner() {
    const { auth } = usePage().props;

    if (!auth.user || auth.user.email_verified_at) {
        return null;
    }

    return (
        <div className="flex flex-wrap items-center gap-3 border-b border-amber-200/60 bg-amber-50 px-4 py-2.5 text-sm text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-200">
            <MailWarning className="size-4 shrink-0" />
            <span className="flex-1">
                Please verify your email address to unlock everything. Check
                your inbox for the link.
            </span>
            <Form {...send.form()}>
                {({ processing }) => (
                    <Button size="sm" variant="outline" disabled={processing}>
                        {processing && <Spinner />}
                        Resend email
                    </Button>
                )}
            </Form>
        </div>
    );
}
