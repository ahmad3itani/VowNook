import { Link } from '@inertiajs/react';
import { DocSection, Dot, PublicPageShell } from '@/components/public/page-shell';

const EFFECTIVE = 'June 12, 2026';

export default function Privacy() {
    return (
        <PublicPageShell
            title="Privacy Policy — VowNook"
            description="How VowNook collects, uses and protects personal information, in line with Canada's PIPEDA."
            eyebrow={`Effective ${EFFECTIVE} · PIPEDA`}
            heading="Privacy"
            headingAccent="policy."
            intro="We handle personal information under Canada's Personal Information Protection and Electronic Documents Act (PIPEDA). This page explains what we collect, why, and the choices you have."
        >
            <DocSection n="01" title="What we collect">
                <ul>
                    <li><Dot /><span><strong>Account information</strong> — name, email address, password (hashed), account type.</span></li>
                    <li><Dot /><span><strong>Wedding planning data</strong> — guest names and contact details, RSVP responses, meal choices and allergy notes, seating assignments, budgets, checklists, photos and website content you upload.</span></li>
                    <li><Dot /><span><strong>Vendor business data</strong> — business name, category, location, pricing, portfolio media, availability.</span></li>
                    <li><Dot /><span><strong>Marketplace activity</strong> — inquiries, messages, offers, bookings and reviews.</span></li>
                    <li><Dot /><span><strong>Technical data</strong> — log and session data needed to keep the service secure.</span></li>
                </ul>
                <p>
                    <strong>Guest information deserves a special note.</strong> Couples enter details about their
                    guests — including dietary and allergy information, which can reveal health details. We
                    process this solely so couples can plan their event (seating, catering counts, RSVP). It is
                    never used for marketing and never sold. Couples are responsible for having their guests'
                    consent to enter this information.
                </p>
            </DocSection>

            <DocSection n="02" title="How we use it">
                <ul>
                    <li><Dot />To provide the planning workspace, wedding websites, RSVP pages and the marketplace.</li>
                    <li><Dot />To deliver notifications you'd expect — new inquiries, offers, RSVPs, booking confirmations.</li>
                    <li><Dot />To moderate vendor listings and keep reviews tied to real bookings.</li>
                    <li><Dot />To secure the service, prevent abuse and meet legal obligations.</li>
                </ul>
                <p>We do not sell personal information, and we do not use your data for third-party advertising.</p>
            </DocSection>

            <DocSection n="03" title="What's public and what's private">
                <ul>
                    <li><Dot /><span><strong>Private by default:</strong> your planning workspace, guest list, budget and messages. Vendors only see what you include in an inquiry.</span></li>
                    <li><Dot /><span><strong>Public when you choose:</strong> your wedding website and RSVP page only after you press publish; a vendor's profile only after moderation approval.</span></li>
                    <li><Dot /><span><strong>Reviews</strong> display your first name and last initial only.</span></li>
                </ul>
            </DocSection>

            <DocSection n="04" title="Who we share it with">
                <p>
                    Only service providers needed to run the platform — hosting and database infrastructure,
                    email delivery, and payment processing (when payments launch, handled by Stripe; card numbers
                    never touch our servers). Each provider is bound to use the data only to provide their service.
                    We disclose information to authorities only where the law requires it.
                </p>
            </DocSection>

            <DocSection n="05" title="Retention & deletion">
                <ul>
                    <li><Dot />Planning data is kept while your account is active.</li>
                    <li><Dot />When you delete your account, personal information is deleted or anonymised within 30 days, except records we must keep (e.g., financial records of bookings, kept up to 7 years for tax law).</li>
                    <li><Dot />Couples can delete individual guests at any time, which removes their RSVP and seating data.</li>
                </ul>
            </DocSection>

            <DocSection n="06" title="Safeguards">
                <p>
                    Passwords are hashed, traffic is encrypted in transit (TLS), access to production data is
                    restricted, and sessions are protected. No system is perfectly secure, but we design for the
                    sensitivity of what a wedding involves — names, addresses and health-adjacent details of
                    people who never signed up themselves.
                </p>
            </DocSection>

            <DocSection n="07" title="Your rights">
                <p>
                    Under PIPEDA you may request access to the personal information we hold about you, ask us to
                    correct it, withdraw consent, or ask for deletion. Write to us via the{' '}
                    <Link href="/contact" className="underline decoration-[#8a651c]/40 underline-offset-2 hover:text-[#8a651c]">contact page</Link>{' '}
                    (choose "Privacy request") and we will respond within 30 days. If you are not satisfied with
                    our response, you may complain to the Office of the Privacy Commissioner of Canada.
                </p>
            </DocSection>

            <DocSection n="08" title="Cookies & changes">
                <p>
                    We use only essential cookies — session and security. No advertising or cross-site tracking
                    cookies. If this policy changes materially, we will notify account holders before the change
                    takes effect.
                </p>
            </DocSection>
        </PublicPageShell>
    );
}
