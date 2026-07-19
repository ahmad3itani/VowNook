import { Link } from '@inertiajs/react';
import { DocSection, Dot, PublicPageShell } from '@/components/public/page-shell';

const EFFECTIVE = 'June 12, 2026';

export default function Terms() {
    return (
        <PublicPageShell
            title="Terms of Service — VowNook"
            description="The terms that govern your use of VowNook — the wedding planning studio and vendor marketplace."
            eyebrow={`Effective ${EFFECTIVE}`}
            heading="Terms of"
            headingAccent="service."
            intro="The plain-language agreement between you and VowNook. By creating an account or using the platform, you agree to these terms."
        >
            <DocSection n="01" title="Who we are & what we provide">
                <p>
                    VowNook ("we", "us", "the platform") operates a wedding planning workspace for
                    couples and a marketplace that connects couples with independent wedding vendors in Canada.
                </p>
                <p>
                    <strong>We are an intermediary, not a party to your wedding contracts.</strong> When a couple
                    accepts a vendor's offer, the resulting agreement for services is between the couple and the
                    vendor. We provide the tools — discovery, messaging, structured quotes, booking records — but
                    we do not perform wedding services and are not responsible for their delivery.
                </p>
            </DocSection>

            <DocSection n="02" title="Accounts">
                <ul>
                    <li><Dot />You must be at least 18 years old and provide accurate information.</li>
                    <li><Dot />You are responsible for activity under your account and for keeping your password secure.</li>
                    <li><Dot />Couple accounts may invite collaborators; you are responsible for who you invite and the access you grant them.</li>
                    <li><Dot />Vendor accounts must represent a real business and have authority to act for it.</li>
                </ul>
            </DocSection>

            <DocSection n="03" title="Fees">
                <p>
                    <strong>Couples:</strong> planning, browsing, requesting quotes and booking are free. The
                    optional Premium tier is a paid upgrade, charged at purchase and non-refundable
                    once the wedding website is published.
                </p>
                <p>
                    <strong>Vendors:</strong> listing is free. We charge a success fee only when a booking is
                    won through the platform: 8% of the first $5,000 of the booking total, 5% of the amount above
                    that, capped at $1,000 per booking. Fee rates for new bookings may change with 30 days'
                    notice; bookings already confirmed keep the rate in force when they were made.
                </p>
                <p>
                    Circumventing the platform to avoid fees (moving a platform-originated lead off-platform to
                    complete the same booking) is a breach of these terms and may lead to suspension.
                </p>
            </DocSection>

            <DocSection n="04" title="Marketplace conduct">
                <ul>
                    <li><Dot />Vendor profiles are moderated before publishing and must be truthful — your own work, real pricing, services you actually deliver.</li>
                    <li><Dot />Offers sent to couples are commitments to honour the quoted price and terms until the offer's expiry date.</li>
                    <li><Dot />Reviews may only be written by couples with a confirmed booking, one per booking. Buying, trading or coercing reviews is prohibited. Vendors may respond publicly but cannot pay to remove or reorder reviews.</li>
                    <li><Dot />No harassment, discrimination, spam or unlawful content anywhere on the platform.</li>
                </ul>
            </DocSection>

            <DocSection n="05" title="Your content">
                <p>
                    You keep ownership of everything you upload — photos, text, business information. You grant
                    us a licence to host, display and process that content to operate the platform (for example,
                    showing a vendor's gallery on their public profile, or a couple's photos on their published
                    wedding website). You are responsible for having the rights to what you upload, including
                    photographs of other people.
                </p>
            </DocSection>

            <DocSection n="06" title="Cancellations & disputes">
                <p>
                    Cancellation and refund terms for wedding services are set in each vendor's offer. Disputes
                    about a booking should first be raised between the couple and the vendor through the inquiry
                    thread; we may assist with records but do not arbitrate service quality.
                </p>
            </DocSection>

            <DocSection n="07" title="Suspension & termination">
                <p>
                    You can close your account at any time (see the <Link href="/privacy" className="underline decoration-[#1f5142]/40 underline-offset-2 hover:text-[#1f5142]">Privacy Policy</Link> for
                    what happens to your data). We may suspend or remove accounts that breach these terms,
                    with notice where practicable.
                </p>
            </DocSection>

            <DocSection n="08" title="Liability">
                <p>
                    The platform is provided "as is". To the maximum extent permitted by law, our total liability
                    to you for any claim arising from the platform is limited to the greater of the fees you paid
                    us in the 12 months before the claim and $100 CAD. We are not liable for the acts or omissions
                    of vendors or couples, or for indirect or consequential losses. Nothing in these terms limits
                    liability that cannot be limited under applicable law.
                </p>
            </DocSection>

            <DocSection n="09" title="Governing law & changes">
                <p>
                    These terms are governed by the laws of the Province of Ontario and the federal laws of Canada
                    applicable in it. We may update these terms; material changes will be announced on the
                    platform at least 14 days before they take effect.
                </p>
                <p>
                    Questions? <Link href="/contact" className="underline decoration-[#1f5142]/40 underline-offset-2 hover:text-[#1f5142]">Contact us</Link>.
                </p>
            </DocSection>
        </PublicPageShell>
    );
}
