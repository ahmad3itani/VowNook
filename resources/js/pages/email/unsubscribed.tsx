import { Head, Link } from '@inertiajs/react';
import { MailX } from 'lucide-react';

export default function Unsubscribed({ category }: { category: string }) {
    return (
        <div className="flex min-h-screen flex-col items-center justify-center bg-[#f1f0ea] px-6 text-center font-['Instrument_Sans'] text-[#12211b]">
            <Head title="Unsubscribed" />

            <div className="flex size-14 items-center justify-center rounded-full bg-[#1f5142]/10 text-[#1f5142]">
                <MailX className="size-7" />
            </div>
            <h1 className="mt-6 font-['Newsreader'] text-3xl">You're unsubscribed</h1>
            <p className="mt-3 max-w-md text-sm text-[#47534d]">
                You'll no longer receive <strong>{category}</strong> emails. You can change this any
                time from your notification settings.
            </p>
            <Link
                href="/settings/notifications"
                className="mt-8 border border-[#12211b] px-6 py-2.5 text-xs tracking-widest text-[#12211b] uppercase transition-colors hover:bg-[#12211b] hover:text-white"
            >
                Manage preferences
            </Link>
        </div>
    );
}
