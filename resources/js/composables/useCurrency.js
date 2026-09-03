import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { formatCurrency as formatCurrencyUtil, getCurrencySymbol as getCurrencySymbolUtil } from '@/lib/currency';

export function useCurrency() {
    const page = usePage();
    
    // Organization currency or default to TZS
    const defaultCurrency = computed(() => {
        return page.props.auth?.organization?.currency || page.props.defaultCurrency || 'TZS';
    });

    const format = (amount, currencyCode = null, forceDecimals = false) => {
        return formatCurrencyUtil(amount, currencyCode || defaultCurrency.value, forceDecimals);
    };

    const getSymbol = (currencyCode = null) => {
        return getCurrencySymbolUtil(currencyCode || defaultCurrency.value);
    };

    return {
        defaultCurrency,
        formatCurrency: format,
        getCurrencySymbol: getSymbol,
    };
}

export default useCurrency;
