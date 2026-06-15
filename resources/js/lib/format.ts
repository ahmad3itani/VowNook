const moneyFormat = new Intl.NumberFormat('en-CA', {
    style: 'currency',
    currency: 'CAD',
    maximumFractionDigits: 0,
});

const moneyFormatDecimals = new Intl.NumberFormat('en-CA', {
    style: 'currency',
    currency: 'CAD',
    maximumFractionDigits: 2,
});

const dateFormat = new Intl.DateTimeFormat('en-CA', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
});

const longDateFormat = new Intl.DateTimeFormat('en-CA', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
});

/** Format an amount in cents as CAD currency (whole dollars by default). Returns '' for nullish input. */
export function formatMoney(cents: number | null | undefined, opts?: { decimals?: boolean }): string {
    if (cents === null || cents === undefined) return '';
    const formatter = opts?.decimals ? moneyFormatDecimals : moneyFormat;
    return formatter.format(cents / 100);
}

function toDate(value: string | Date | null | undefined): Date | null {
    if (!value) return null;
    const date = value instanceof Date ? value : new Date(value);
    return Number.isNaN(date.getTime()) ? null : date;
}

/** Format a date as e.g. "Jun 11, 2026". Returns '' for nullish or invalid input. */
export function formatDate(value: string | Date | null | undefined): string {
    const date = toDate(value);
    return date ? dateFormat.format(date) : '';
}

/** Format a date as e.g. "Thursday, June 11, 2026". Returns '' for nullish or invalid input. */
export function formatLongDate(value: string | Date | null | undefined): string {
    const date = toDate(value);
    return date ? longDateFormat.format(date) : '';
}
