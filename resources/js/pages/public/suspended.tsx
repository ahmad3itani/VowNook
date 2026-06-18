import { Head, Link } from '@inertiajs/react';
import { ShieldAlert } from 'lucide-react';

export default function Suspended() {
    return (
        <>
            <Head title="Account suspended">
                <meta name="robots" content="noindex" />
            </Head>

            <div className="flex min-h-screen flex-col items-center justify-center gap-5 bg-[#faf6ef] px-6 text-center text-[#1e1b17]">
                <div className="flex size-14 items-center justify-center rounded-full bg-[#8a651c]/10 text-[#8a651c]">
                    <ShieldAlert className="size-7" />
                </div>
                <h1 className="font-serif text-2xl font-semibold">Your account is suspended</h1>
                <p className="max-w-md text-sm text-[#52493d]">
                    Access to this account has been paused. If you think this is a mistake,
                    please get in touch and our team will look into it.
                </p>
                <div className="flex gap-3">
                    <Link href="/contact" className="rounded-lg bg-[#8a651c] px-5 py-2.5 text-sm font-medium text-white hover:bg-[#735015]">
                        Contact support
                    </Link>
                    <Link href="/" className="rounded-lg border border-[#cec5bd] px-5 py-2.5 text-sm font-medium hover:bg-white">
                        Back home
                    </Link>
                </div>
            </div>
        </>
    );
}
