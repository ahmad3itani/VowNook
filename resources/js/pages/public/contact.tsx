import { useForm } from '@inertiajs/react';
import { CheckCircle2, Send } from 'lucide-react';
import { PublicPageShell } from '@/components/public/page-shell';

const fraunces = "font-['Fraunces']";

const TOPICS = [
    { value: 'couple', label: "I'm planning a wedding" },
    { value: 'vendor', label: "I'm a vendor" },
    { value: 'privacy', label: 'Privacy request' },
    { value: 'partnership', label: 'Partnership / press' },
    { value: 'other', label: 'Something else' },
];

export default function Contact() {
    const { data, setData, post, processing, errors, reset, recentlySuccessful } = useForm({
        name: '',
        email: '',
        topic: 'couple',
        message: '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post('/contact', { preserveScroll: true, onSuccess: () => reset('message') });
    }

    const inputClass =
        'w-full border border-[#191613]/20 bg-white/60 px-4 py-3 text-[15px] text-[#191613] placeholder:text-[#52493d]/50 focus:border-[#8a651c] focus:outline-none';

    return (
        <PublicPageShell
            title="Contact — VowNook"
            description="Questions about planning, vendor listings, privacy or partnerships — write to the VowNook team."
            eyebrow="We read everything"
            heading="Say"
            headingAccent="hello."
            intro="Whether you're planning, listing your business, or exercising a privacy right — write to us. We aim to reply within one business day."
        >
            <div className="grid gap-14 md:grid-cols-12">
                <form onSubmit={submit} className="space-y-5 md:col-span-7">
                    {recentlySuccessful && (
                        <div className="flex items-center gap-3 border border-[#8a651c]/30 bg-[#e9c176]/15 px-4 py-3 text-sm text-[#52493d]">
                            <CheckCircle2 className="size-4 shrink-0 text-[#8a651c]" />
                            Thank you — your message is on its way. We'll reply by email.
                        </div>
                    )}

                    <div className="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label htmlFor="contact-name" className="mb-1.5 block text-[11px] tracking-[0.2em] text-[#52493d] uppercase">
                                Your name
                            </label>
                            <input
                                id="contact-name"
                                type="text"
                                required
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                className={inputClass}
                                placeholder="Avery Lane"
                            />
                            {errors.name && <p className="mt-1 text-sm text-red-700">{errors.name}</p>}
                        </div>
                        <div>
                            <label htmlFor="contact-email" className="mb-1.5 block text-[11px] tracking-[0.2em] text-[#52493d] uppercase">
                                Email
                            </label>
                            <input
                                id="contact-email"
                                type="email"
                                required
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                className={inputClass}
                                placeholder="you@example.com"
                            />
                            {errors.email && <p className="mt-1 text-sm text-red-700">{errors.email}</p>}
                        </div>
                    </div>

                    <div>
                        <label htmlFor="contact-topic" className="mb-1.5 block text-[11px] tracking-[0.2em] text-[#52493d] uppercase">
                            Topic
                        </label>
                        <select
                            id="contact-topic"
                            value={data.topic}
                            onChange={(e) => setData('topic', e.target.value)}
                            className={inputClass}
                        >
                            {TOPICS.map((t) => (
                                <option key={t.value} value={t.value}>{t.label}</option>
                            ))}
                        </select>
                        {errors.topic && <p className="mt-1 text-sm text-red-700">{errors.topic}</p>}
                    </div>

                    <div>
                        <label htmlFor="contact-message" className="mb-1.5 block text-[11px] tracking-[0.2em] text-[#52493d] uppercase">
                            Message
                        </label>
                        <textarea
                            id="contact-message"
                            required
                            rows={6}
                            value={data.message}
                            onChange={(e) => setData('message', e.target.value)}
                            className={inputClass}
                            placeholder="Tell us what's on your mind…"
                        />
                        {errors.message && <p className="mt-1 text-sm text-red-700">{errors.message}</p>}
                    </div>

                    <button
                        type="submit"
                        disabled={processing}
                        className="inline-flex items-center gap-3 bg-[#191613] px-9 py-4 text-[11px] font-semibold tracking-[0.22em] text-[#faf6ef] uppercase transition-colors hover:bg-[#8a651c] disabled:opacity-50"
                    >
                        {processing ? 'Sending…' : 'Send message'}
                        <Send className="size-4" />
                    </button>
                </form>

                <aside className="md:col-span-4 md:col-start-9">
                    <h2 className={`${fraunces} text-2xl font-light`}>Before you write…</h2>
                    <ul className="mt-5 space-y-4 text-sm leading-relaxed text-[#52493d]">
                        <li>
                            <strong className="text-[#191613]">Vendors:</strong> profiles are usually reviewed and
                            published within a day — no need to chase us before then.
                        </li>
                        <li>
                            <strong className="text-[#191613]">Privacy requests</strong> (access, correction,
                            deletion) are answered within 30 days as required by PIPEDA.
                        </li>
                        <li>
                            <strong className="text-[#191613]">Booking questions:</strong> the fastest route is
                            the message thread on the inquiry itself — both sides and our records in one place.
                        </li>
                    </ul>
                </aside>
            </div>
        </PublicPageShell>
    );
}
