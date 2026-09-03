/**
 * Centralized currency formatting utility for Inventoros — Tanzania Business Edition.
 */

const CURRENCY_SYMBOLS = {
    TZS: 'TSh',
    KES: 'KSh',
    UGX: 'USh',
    RWF: 'FRw',
    USD: '$',
    EUR: '€',
    GBP: '£',
    CAD: 'C$',
    AUD: 'A$',
    JPY: '¥',
    CNY: '¥',
    INR: '₹',
    ZAR: 'R',
    AED: 'د.إ',
    SAR: '﷼',
};

const NO_DECIMAL_CURRENCIES = ['TZS', 'UGX', 'RWF', 'JPY', 'KRW'];

export function getCurrencySymbol(currencyCode = 'TZS') {
    if (!currencyCode) return 'TSh';
    const code = currencyCode.toUpperCase();
    return CURRENCY_SYMBOLS[code] || code;
}

export function formatCurrency(amount, currencyCode = 'TZS', forceDecimals = false) {
    if (amount === null || amount === undefined || isNaN(Number(amount))) {
        return '-';
    }

    const num = Number(amount);
    const code = (currencyCode || 'TZS').toUpperCase();
    const symbol = getCurrencySymbol(code);

    const isNoDecimal = NO_DECIMAL_CURRENCIES.includes(code);
    const hasFractions = num % 1 !== 0;

    let decimals = 0;
    if (forceDecimals || (!isNoDecimal && hasFractions)) {
        decimals = 2;
    } else if (!isNoDecimal && !forceDecimals) {
        decimals = hasFractions ? 2 : 0;
    }

    const formattedNumber = new Intl.NumberFormat('en-US', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    }).format(num);

    if (['TZS', 'KES', 'UGX', 'RWF'].includes(code)) {
        return `${symbol} ${formattedNumber}`;
    }

    if (['$', '€', '£', '¥', '₹'].includes(symbol)) {
        return `${symbol}${formattedNumber}`;
    }

    return `${symbol} ${formattedNumber}`;
}

export default {
    formatCurrency,
    getCurrencySymbol,
    CURRENCY_SYMBOLS,
};
