import { Head, Link } from '@inertiajs/react';
import { MailX } from 'lucide-react';

export default function Unsubscribed({ category }: { category: string }) {
    return (
        <div className="flex min-h-screen flex-col items-center justify-center bg-[#faf6ef] px-6 text-center font-['DM_Sans'] text-[#1e1b17]">
            <Head title="Unsubscribed" />

            <div className="flex size-14 items-center justify-center rounded-full bg-[#8a651c]/10 text-[#8a651c]">
                <MailX className="size-7" />
            </div>
            <h1 className="mt-6 font-['Fraunces'] text-3xl">You're unsubscribed</h1>
            <p className="mt-3 max-w-md text-sm text-[#4c4640]">
                You'll no longer receive <strong>{category}</strong> emails. You can change this any
                time from your notification settings.
            </p>
            <Link
                href="/settings/notifications"
                className="mt-8 border border-[#1e1b17] px-6 py-2.5 text-xs tracking-widest text-[#1e1b17] uppercase transition-colors hover:bg-[#1e1b17] hover:text-white"
            >
                Manage preferences
            </Link>
        </div>
    );
}
