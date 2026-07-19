import { Link } from '@inertiajs/react';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

const fraunces = "font-['Newsreader']";

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="flex min-h-svh bg-[#f1f0ea] font-['Instrument_Sans'] text-[#0f1c17]">
            {/* Editorial image panel */}
            <div className="relative hidden w-[44%] overflow-hidden lg:block">
                <img
                    src="/images/landing/hero.webp"
                    alt=""
                    className="absolute inset-0 size-full object-cover"
                />
                <div className="absolute inset-0 bg-gradient-to-t from-[#0f1c17]/80 via-[#0f1c17]/20 to-[#0f1c17]/30" />
                <div className="absolute inset-x-0 bottom-0 p-12">
                    <p className="mb-4 text-[10px] tracking-[0.35em] text-[#7fb79e] uppercase">
                        VowNook
                    </p>
                    <p className={`${fraunces} max-w-md text-3xl leading-snug font-light text-white`}>
                        The best days are <em className="text-[#7fb79e]">planned together.</em>
                    </p>
                </div>
            </div>

            {/* Form panel */}
            <div className="flex flex-1 flex-col items-center justify-center p-6 md:p-10">
                <div className="w-full max-w-sm">
                    <div className="flex flex-col gap-8">
                        <div className="flex flex-col gap-4">
                            <Link href={home()} className="flex w-fit items-center gap-2.5" aria-label="VowNook home">
                                <img src="/images/brand/logo-mark.svg" alt="" className="size-9 rounded-md border border-[#0f1c17]/10" />
                                <span className={`${fraunces} text-2xl font-medium tracking-tight`}>VowNook</span>
                            </Link>

                            <div className="space-y-1.5">
                                <h1 className={`${fraunces} text-2xl font-light`}>{title}</h1>
                                <p className="text-sm text-[#4b5850]">{description}</p>
                            </div>
                        </div>
                        {children}
                    </div>
                </div>
            </div>
        </div>
    );
}
