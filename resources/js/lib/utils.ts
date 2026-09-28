import { type ClassValue, clsx } from "clsx";
import { twMerge } from "tailwind-merge";

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function toUrl(url: string | undefined): string {
    return url || '#';
}

export function formatCurrency(amount: number | string | null | undefined, currencySymbol: string = '₦'): string {
    const num = Number(amount) || 0;
    const formatted = new Intl.NumberFormat('en-NG', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Math.abs(num));

    return (num < 0 ? '-' : '') + currencySymbol + formatted;
}

export function formatCompactCurrency(amount: number | string | null | undefined, currencySymbol: string = '₦'): string {
    const num = Number(amount) || 0;
    const absNum = Math.abs(num);
    const sign = num < 0 ? '-' : '';

    if (absNum >= 1_000_000_000) {
        const value = absNum / 1_000_000_000;
        const formatted = Number.isInteger(value) ? value.toString() : value.toFixed(1).replace(/\.0$/, '');
        return `${sign}${currencySymbol}${formatted}B`;
    }

    if (absNum >= 1_000_000) {
        const value = absNum / 1_000_000;
        const formatted = Number.isInteger(value) ? value.toString() : value.toFixed(1).replace(/\.0$/, '');
        return `${sign}${currencySymbol}${formatted}M`;
    }

    if (absNum >= 1_000) {
        const value = absNum / 1_000;
        const formatted = Number.isInteger(value) ? value.toString() : value.toFixed(1).replace(/\.0$/, '');
        return `${sign}${currencySymbol}${formatted}K`;
    }

    return formatCurrency(num, currencySymbol);
}
