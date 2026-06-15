import { Link } from '@inertiajs/react';
import { DocSection, Dot, PublicPageShell } from '@/components/public/page-shell';

const EFFECTIVE = 'June 15, 2026';

export default function MarketplaceRules() {
    return (
        <PublicPageShell
            title="Marketplace Rules — VowNook"
            description="The rules that keep the VowNook marketplace safe and trustworthy — accurate listings, real reviews, no off-platform circumvention, and how we handle reports and fraud."
            eyebrow={`Effective ${EFFECTIVE}`}
            heading="Marketplace"
            headingAccent="rules."
            intro="A trustworthy marketplace protects everyone. These rules apply to couples and vendors alike — break them and we can remove content or suspend accounts."
        >
            <DocSection n="01" title="Real businesses, honest listings">
                <ul>
                    <li><Dot />Vendors must represent a real business and have authority to act for it. Listings are reviewed before they go live.</li>
                    <li><Dot />Portfolios must be your own work. Passing off someone else's photography or projects as yours is grounds for removal.</li>
                    <li><Dot />Pricing, services and availability must be accurate and kept up to date.</li>
                </ul>
            </DocSection>

            <DocSection n="02" title="Real reviews only">
                <p>
                    Every review on VowNook is tied to a confirmed booking — one review per booking. Buying, trading,
                    incentivising or coercing reviews is prohibited, and vendors can never pay to remove or reorder them.
                    Vendors may respond publicly and professionally.
                </p>
            </DocSection>

            <DocSection n="03" title="Keep transactions on-platform">
                <p>
                    Quotes, bookings and payments happen through VowNook so couples are protected and reviews stay
                    verifiable. Moving a platform-originated lead off-platform to avoid fees ("circumvention") is a
                    breach of these rules and the <Link href="/vendor-agreement" className="underline decoration-[#8a651c]/40 underline-offset-2 hover:text-[#8a651c]">Vendor Agreement</Link>.
                </p>
            </DocSection>

            <DocSection n="04" title="Prohibited conduct">
                <ul>
                    <li><Dot />Fraud, impersonation, or misrepresenting who you are or what you offer.</li>
                    <li><Dot />Harassment, discrimination, hate, or unlawful content anywhere on the platform.</li>
                    <li><Dot />Spam, scraping, or attempts to manipulate search ranking or reviews.</li>
                    <li><Dot />Requesting payment details, deposits or full payment outside the platform's tools.</li>
                </ul>
            </DocSection>

            <DocSection n="05" title="Reporting & enforcement">
                <p>
                    See something off? Use the <strong>Report</strong> link on any vendor listing or review. Reports go
                    straight to our moderation team. We may warn, unpublish, or suspend accounts that break these rules,
                    and we remove listings we can't verify. Verified vendors carry a <strong>Verified</strong> badge after
                    a manual check.
                </p>
                <p>
                    Questions? <Link href="/contact" className="underline decoration-[#8a651c]/40 underline-offset-2 hover:text-[#8a651c]">Contact us</Link>.
                </p>
            </DocSection>
        </PublicPageShell>
    );
}
