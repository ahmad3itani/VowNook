export type InquiryMessage = {
    id: number;
    body: string;
    is_mine: boolean;
    sender_name: string | null;
    created_at: string;
    /** Present on the couple-side payload only. */
    sender_id?: number;
    /** Present on the couple-side payload only. */
    read_at?: string | null;
};

export type OfferLineItem = {
    name: string;
    amount_cents: number;
    qty?: number;
};

export type InquiryOffer = {
    id: number;
    total_cents: number;
    deposit_cents: number;
    line_items: OfferLineItem[];
    terms: string | null;
    valid_until: string | null;
    status: string;
    status_label: string;
};

export type InquiryReview = {
    rating: number;
    body: string | null;
    vendor_response: string | null;
    /** Present on the vendor-side payload only (needed to post a response). */
    id?: number;
};

export type InquiryBooking = {
    id: number;
    total_cents: number;
    deposit_cents: number;
    status: string;
    status_label: string;
    /** Payments (Phase 4). Present on the couple-side payload. */
    deposit_due_cents?: number;
    balance_due_cents?: number;
    amount_paid_cents?: number;
    payments_configured?: boolean;
    vendor_can_receive?: boolean;
};

export type InquiryVendorSummary = {
    business_name: string;
    slug: string;
    category_label: string | null;
    logo_url: string | null;
};

export type InquiryBase = {
    id: number;
    status: string;
    status_label: string;
    message: string;
    event_date: string | null;
    guest_count: number | null;
    budget_cents: number | null;
    service: { name: string } | null;
    offer: InquiryOffer | null;
    messages: InquiryMessage[];
};

/** Inquiry as seen by the couple (pages/inquiries/show.tsx). */
export type CoupleInquiry = InquiryBase & {
    vendor: InquiryVendorSummary;
    booking: InquiryBooking | null;
    review: InquiryReview | null;
    current_user_id: number;
};

/** Inquiry as seen by the vendor (pages/vendor/inquiry-show.tsx). */
export type VendorInquiry = InquiryBase & {
    wedding_name: string | null;
    review: InquiryReview | null;
};
