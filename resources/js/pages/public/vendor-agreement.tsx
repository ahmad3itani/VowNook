import { Link } from '@inertiajs/react';
import { DocSection, Dot, PublicPageShell } from '@/components/public/page-shell';

const EFFECTIVE = 'June 15, 2026';

export default function VendorAgreement() {
    return (
        <PublicPageShell
            title="Vendor Agreement — VowNook"
            description="The terms every VowNook vendor agrees to: accurate listings, owning your portfolio, honouring quotes, the success-fee model, prohibited conduct, and grounds for suspension."
            eyebrow={`Effective ${EFFECTIVE}`}
            heading="Vendor"
            headingAccent="agreement."
            intro="By submitting a listing to the marketplace you agree to these terms, in addition to our Terms of Service and Marketplace Rules. We record your acceptance when you submit for review."
        >
            <DocSection n="01" title="Your business & your listing">
                <ul>
                    <li><Dot />You represent a real, lawfully operating business and have authority to list it.</li>
                    <li><Dot />Your profile, pricing and services are accurate and kept current.</li>
                    <li><Dot />You own or are licensed to use every photo, video and document you upload, including images of other people.</li>
                </ul>
            </DocSection>

            <DocSection n="02" title="Quotes & bookings">
                <p>
                    Offers you send are commitments to honour the quoted price and terms until the offer's expiry.
                    When a couple accepts, the service contract is between you and the couple — VowNook is the
                    intermediary, not a party to it. Cancellation and refund terms are whatever you set in your offer.
                </p>
            </DocSection>

            <DocSection n="03" title="Fees & the success-fee model">
                <p>
                    Listing is free. We charge a success fee only when you win a booking through the platform:
                    8% of the first $5,000 of the booking total, 5% of the amount above that, capped at $1,000 per
                    booking. Rates for new bookings may change with 30 days' notice; confirmed bookings keep their rate.
                </p>
                <p>
                    <strong>No circumvention.</strong> Taking a platform-originated lead off-platform to avoid the fee
                    is a material breach and grounds for suspension.
                </p>
            </DocSection>

            <DocSection n="04" title="Reviews & conduct">
                <p>
                    Reviews come only from couples with a confirmed booking. You may respond professionally but must
                    never buy, trade, incentivise or coerce reviews. No harassment, discrimination, spam or unlawful
                    conduct. Payment requests must stay within the platform's tools.
                </p>
            </DocSection>

            <DocSection n="05" title="Verification, moderation & suspension">
                <p>
                    Listings are reviewed before publishing. We may request proof of business identity and can grant a
                    <strong> Verified</strong> badge after a manual check. We can unpublish or suspend listings that
                    breach this agreement, the <Link href="/marketplace-rules" className="underline decoration-[#1f5142]/40 underline-offset-2 hover:text-[#1f5142]">Marketplace Rules</Link>, or that we cannot verify — with notice where practicable.
                </p>
                <p>
                    Questions? <Link href="/contact" className="underline decoration-[#1f5142]/40 underline-offset-2 hover:text-[#1f5142]">Contact us</Link>.
                </p>
            </DocSection>
        </PublicPageShell>
    );
}
