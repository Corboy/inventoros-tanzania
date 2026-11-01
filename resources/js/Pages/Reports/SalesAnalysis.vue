<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import Card from '@/Components/ui/Card.vue';
import CardHeader from '@/Components/ui/CardHeader.vue';
import Button from '@/Components/ui/Button.vue';
import Badge from '@/Components/ui/Badge.vue';
import StatTile from '@/Components/ui/StatTile.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useCurrency } from '@/composables/useCurrency';
import {
    ArrowLeft,
    Download,
    Printer,
    ShoppingCart,
    DollarSign,
    Boxes,
    TrendingUp,
    Calendar,
    CreditCard,
    Users,
    CheckCircle2,
    Clock,
    ArrowUpRight,
    Percent,
    FileSpreadsheet,
    Sparkles,
} from 'lucide-vue-next';

const { t } = useI18n();
const { formatCurrency } = useCurrency();

const props = defineProps({
    summary: {
        type: Object,
        default: () => ({
            total_orders: 0,
            total_revenue: 0,
            total_items_sold: 0,
            average_order_value: 0,
            total_cost: 0,
            gross_profit: 0,
            profit_margin: 0,
        }),
    },
    byStatus: {
        type: Array,
        default: () => [],
    },
    topProducts: {
        type: Array,
        default: () => [],
    },
    dailySales: {
        type: Array,
        default: () => [],
    },
    paymentMethods: {
        type: Array,
        default: () => [],
    },
    salesByUser: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const activePeriod = ref(props.filters?.period || 'last_30_days');
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');
const showCustomPicker = ref(props.filters?.period === 'custom');

const periodPresets = [
    { id: 'today', label: 'Leo (Siku)' },
    { id: 'yesterday', label: 'Jana' },
    { id: 'this_week', label: 'Wiki Hii' },
    { id: 'last_7_days', label: 'Siku 7' },
    { id: 'this_month', label: 'Mwezi Huu' },
    { id: 'last_month', label: 'Mwezi Uliopita' },
    { id: 'this_year', label: 'Mwaka Huu' },
    { id: 'custom', label: 'Kipindi Maalum' },
];

const selectPeriod = (periodId) => {
    activePeriod.value = periodId;
    if (periodId === 'custom') {
        showCustomPicker.value = true;
        return;
    }
    showCustomPicker.value = false;
    router.get(route('reports.sales-analysis'), {
        period: periodId,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const applyCustomDates = () => {
    router.get(route('reports.sales-analysis'), {
        period: 'custom',
        date_from: dateFrom.value,
        date_to: dateTo.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const printReport = () => {
    window.print();
};

const downloadCsv = () => {
    const params = new URLSearchParams();
    params.set('export', 'csv');
    if (activePeriod.value) {
        params.set('period', activePeriod.value);
    }
    if (activePeriod.value === 'custom') {
        if (dateFrom.value) params.set('date_from', dateFrom.value);
        if (dateTo.value) params.set('date_to', dateTo.value);
    }
    window.location.href = `${route('reports.sales-analysis')}?${params.toString()}`;
};

const statusVariant = (status) =>
    ({
        delivered: 'success',
        shipped: 'brand',
        processing: 'info',
        pending: 'warning',
        cancelled: 'danger',
    }[status] || 'neutral');

const statusLabel = (status) =>
    ({
        delivered: 'Imekamilika',
        shipped: 'Imesafirishwa',
        processing: 'Inashughulikiwa',
        pending: 'Inasubiri',
        cancelled: 'Imeghairiwa',
    }[status] || status);

const paymentMethodLabel = (method) =>
    ({
        cash: 'Pesa Taslimu (Cash)',
        mpesa: 'M-Pesa (Vodacom)',
        tigopesa: 'Tigo Pesa',
        airtelmoney: 'Airtel Money',
        halopesa: 'HaloPesa',
        bank: 'Benki / Kadi (CRDB/NMB)',
        credit: 'Daftari la Deni',
    }[method?.toLowerCase()] || (method ? method.toUpperCase() : 'Pesa Taslimu'));

// Bar heights for the sales visual, scaled to the largest revenue
const maxTrendRevenue = computed(() =>
    Math.max(1, ...(props.dailySales || []).map((item) => Number(item.revenue) || 0))
);

const barHeight = (revenue) => {
    const pct = (Number(revenue) || 0) / maxTrendRevenue.value;
    return `${Math.max(6, Math.round(pct * 100))}%`;
};

const formatTrendLabel = (val) => {
    if (!val) return '';
    if (props.filters?.granularity === 'hour') {
        return val;
    }
    if (props.filters?.granularity === 'month') {
        const parts = val.split('-');
        if (parts.length === 2) {
            const months = ['Jan', 'Feb', 'Mac', 'Apr', 'Mei', 'Jun', 'Jul', 'Ago', 'Sep', 'Okt', 'Nov', 'Des'];
            const mIndex = parseInt(parts[1], 10) - 1;
            return `${months[mIndex] || parts[1]} ${parts[0]}`;
        }
        return val;
    }
    // Daily format
    try {
        const d = new Date(val);
        return d.toLocaleDateString('sw-TZ', { day: '2-digit', month: 'short' });
    } catch {
        return val;
    }
};

const inputClass =
    'h-9 w-full rounded-lg border border-border-subtle bg-surface-canvas px-3 text-sm text-text-primary focus:border-brand focus:ring-1 focus:ring-brand';
const thClass =
    'px-4 py-3 text-left text-xs font-semibold tracking-wider uppercase text-text-secondary bg-surface-overlay/50';
</script>

<template>
    <Head :title="`${t('reports.salesAnalysis.title')} - ${filters?.period_label || 'Ripoti'}`" />

    <AppLayout>
        <template #header>
            <div class="flex items-center gap-2 text-xs">
                <span class="text-text-tertiary">Biashara</span>
                <span class="text-text-tertiary">/</span>
                <Link :href="route('reports.index')" class="text-text-tertiary transition-colors hover:text-text-primary">{{ t('reports.title') }}</Link>
                <span class="text-text-tertiary">/</span>
                <span class="font-medium text-text-primary">{{ t('reports.salesAnalysis.title') }}</span>
            </div>
        </template>

        <!-- Printable Document Header (visible only when printing) -->
        <div class="hidden print:block mb-6 border-b border-gray-300 pb-4">
            <h1 class="text-2xl font-bold text-gray-900">INVENTOROS TANZANIA - RIPOTI YA MAUZO NA FAIDA</h1>
            <p class="text-sm text-gray-600 mt-1">Kipindi: <strong>{{ filters?.period_label }}</strong> | Tarehe ya Kuchapa: {{ new Date().toLocaleString('sw-TZ') }}</p>
        </div>

        <PageHeader
            :title="t('reports.salesAnalysis.title')"
            :description="filters?.period_label ? `Kipindi: ${filters.period_label}` : t('reports.salesAnalysis.description')"
            class="print:hidden"
        >
            <template #actions>
                <Button variant="secondary" size="sm" as="Link" :href="route('reports.index')" class="shadow-sm">
                    <ArrowLeft :size="14" />
                    <span>{{ t('reports.backToReports') }}</span>
                </Button>
                <Button variant="secondary" size="sm" @click="downloadCsv" class="shadow-sm">
                    <FileSpreadsheet :size="14" class="text-emerald-600" />
                    <span>Pakua Excel / CSV</span>
                </Button>
                <Button variant="default" size="sm" @click="printReport" class="shadow-sm bg-brand hover:bg-brand-emphasis">
                    <Printer :size="14" />
                    <span>Chapisha Ripoti</span>
                </Button>
            </template>
        </PageHeader>

        <!-- Fast Period Preset Switcher -->
        <div class="mt-6 print:hidden">
            <div class="rounded-xl border border-border-subtle bg-surface-card p-2 shadow-xs">
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <div class="flex items-center gap-1.5 overflow-x-auto py-1 scrollbar-none">
                        <span class="text-xs font-semibold text-text-tertiary px-2 flex items-center gap-1">
                            <Calendar :size="14" />
                            Kipindi:
                        </span>
                        <button
                            v-for="preset in periodPresets"
                            :key="preset.id"
                            @click="selectPeriod(preset.id)"
                            type="button"
                            :class="[
                                'px-3 py-1.5 text-xs font-medium rounded-lg transition-all whitespace-nowrap cursor-pointer',
                                activePeriod === preset.id
                                    ? 'bg-brand text-white shadow-xs font-semibold ring-2 ring-brand/20'
                                    : 'text-text-secondary hover:text-text-primary hover:bg-surface-overlay'
                            ]"
                        >
                            {{ preset.label }}
                        </button>
                    </div>
                </div>

                <!-- Expandable Custom Date Inputs -->
                <transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="transform -translate-y-2 opacity-0"
                    enter-to-class="transform translate-y-0 opacity-100"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="transform translate-y-0 opacity-100"
                    leave-to-class="transform -translate-y-2 opacity-0"
                >
                    <form v-if="showCustomPicker" @submit.prevent="applyCustomDates" class="mt-3 pt-3 border-t border-border-subtle">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <div>
                                <label for="date_from" class="mb-1 block text-xs font-medium text-text-secondary">Kuanzia Tarehe</label>
                                <input id="date_from" v-model="dateFrom" type="date" :class="inputClass" required />
                            </div>
                            <div>
                                <label for="date_to" class="mb-1 block text-xs font-medium text-text-secondary">Mpaka Tarehe</label>
                                <input id="date_to" v-model="dateTo" type="date" :class="inputClass" required />
                            </div>
                            <div class="flex items-end">
                                <Button type="submit" variant="default" size="sm" class="w-full sm:w-auto">
                                    {{ t('reports.salesAnalysis.apply') }}
                                </Button>
                            </div>
                        </div>
                    </form>
                </transition>
            </div>
        </div>

        <!-- 4 Top KPI Cards (Clean, Readable, High-Impact) -->
        <section class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <!-- Gross Revenue -->
            <div class="relative overflow-hidden rounded-2xl border border-emerald-500/20 bg-emerald-50/40 dark:bg-emerald-950/20 p-5 shadow-xs transition-all hover:shadow-md">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold tracking-wider uppercase text-emerald-700 dark:text-emerald-400">Jumla ya Mapato</span>
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-500 text-white shadow-xs">
                        <DollarSign :size="18" />
                    </span>
                </div>
                <div class="mt-3">
                    <h3 class="text-2xl font-extrabold tracking-tight text-emerald-950 dark:text-emerald-100 tabular-nums">
                        {{ formatCurrency(summary.total_revenue) }}
                    </h3>
                    <p class="mt-1 flex items-center gap-1.5 text-xs text-emerald-800 dark:text-emerald-300">
                        <ShoppingCart :size="13" />
                        <span>Miamala <strong>{{ summary.total_orders }}</strong> iliyokamilika</span>
                    </p>
                </div>
            </div>

            <!-- Gross Profit -->
            <div class="relative overflow-hidden rounded-2xl border border-blue-500/20 bg-blue-50/40 dark:bg-blue-950/20 p-5 shadow-xs transition-all hover:shadow-md">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold tracking-wider uppercase text-blue-700 dark:text-blue-400">Faida Halisi / Ghafi</span>
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-600 text-white shadow-xs">
                        <TrendingUp :size="18" />
                    </span>
                </div>
                <div class="mt-3">
                    <h3 class="text-2xl font-extrabold tracking-tight text-blue-950 dark:text-blue-100 tabular-nums">
                        {{ formatCurrency(summary.gross_profit) }}
                    </h3>
                    <div class="mt-1 flex items-center gap-2">
                        <span class="inline-flex items-center gap-0.5 rounded-md bg-blue-500/10 px-1.5 py-0.5 text-xs font-bold text-blue-700 dark:text-blue-300">
                            <ArrowUpRight :size="12" />
                            {{ summary.profit_margin }}% Margin
                        </span>
                        <span class="text-xs text-text-tertiary">ya faida ya bidhaa</span>
                    </div>
                </div>
            </div>

            <!-- Cost of Goods Sold (COGS) -->
            <div class="relative overflow-hidden rounded-2xl border border-amber-500/20 bg-amber-50/40 dark:bg-amber-950/20 p-5 shadow-xs transition-all hover:shadow-md">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold tracking-wider uppercase text-amber-700 dark:text-amber-400">Gharama za Ununuzi (COGS)</span>
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-500 text-white shadow-xs">
                        <Boxes :size="18" />
                    </span>
                </div>
                <div class="mt-3">
                    <h3 class="text-2xl font-extrabold tracking-tight text-amber-950 dark:text-amber-100 tabular-nums">
                        {{ formatCurrency(summary.total_cost) }}
                    </h3>
                    <p class="mt-1 text-xs text-amber-800 dark:text-amber-300">
                        Mtaji halisi wa bidhaa zilizouzwa
                    </p>
                </div>
            </div>

            <!-- Average Order Value (AOV) -->
            <div class="relative overflow-hidden rounded-2xl border border-purple-500/20 bg-purple-50/40 dark:bg-purple-950/20 p-5 shadow-xs transition-all hover:shadow-md">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold tracking-wider uppercase text-purple-700 dark:text-purple-400">Wastani kwa Kila Oda</span>
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-purple-600 text-white shadow-xs">
                        <Percent :size="18" />
                    </span>
                </div>
                <div class="mt-3">
                    <h3 class="text-2xl font-extrabold tracking-tight text-purple-950 dark:text-purple-100 tabular-nums">
                        {{ formatCurrency(summary.average_order_value) }}
                    </h3>
                    <p class="mt-1 text-xs text-purple-800 dark:text-purple-300">
                        Vipande <strong>{{ summary.total_items_sold }}</strong> viliuzwa
                    </p>
                </div>
            </div>
        </section>

        <!-- Visual Sales Trend Chart (Hourly / Daily / Monthly) -->
        <Card class="mt-6 overflow-hidden" :padded="false">
            <div class="border-b border-border-subtle p-4 sm:px-6 flex items-center justify-between flex-wrap gap-2">
                <div>
                    <h3 class="text-base font-bold text-text-primary flex items-center gap-2">
                        <TrendingUp :size="18" class="text-brand" />
                        Mwenendo wa Mauzo ({{ filters?.granularity === 'hour' ? 'Kwa Kila Saa' : filters?.granularity === 'month' ? 'Kwa Kila Mwezi' : 'Kwa Kila Siku' }})
                    </h3>
                    <p class="text-xs text-text-tertiary mt-0.5">Ulinganifu wa mapato yaliyoingia kwa wakati</p>
                </div>
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-surface-overlay px-2.5 py-1 text-xs font-medium text-text-secondary">
                    <Sparkles :size="13" class="text-amber-500" />
                    Kilele cha Mauzo: <strong class="text-text-primary">{{ formatCurrency(maxTrendRevenue) }}</strong>
                </span>
            </div>

            <div class="p-6">
                <div v-if="dailySales && dailySales.length > 0" class="flex items-end justify-between gap-2 sm:gap-3 h-52 border-b border-border-subtle pb-2 overflow-x-auto">
                    <div
                        v-for="item in dailySales"
                        :key="item.date"
                        class="group relative flex h-full min-w-[2.2rem] flex-1 flex-col items-center justify-end"
                    >
                        <!-- Tooltip on hover -->
                        <div class="absolute -top-10 z-10 hidden rounded-md bg-neutral-900 px-2 py-1 text-[11px] font-medium text-white shadow-lg whitespace-nowrap group-hover:block dark:bg-neutral-100 dark:text-neutral-900">
                            {{ item.date }}: {{ formatCurrency(item.revenue) }} ({{ item.orders }} oda)
                        </div>

                        <!-- Bar -->
                        <div class="w-full max-w-[2.5rem] rounded-t-lg bg-gradient-to-t from-brand-emphasis to-brand transition-all duration-300 group-hover:from-emerald-600 group-hover:to-emerald-400"
                            :style="{ height: barHeight(item.revenue) }"
                        ></div>

                        <!-- Date/Hour Label -->
                        <span class="mt-2 text-[10px] font-medium text-text-tertiary group-hover:text-text-primary truncate max-w-full text-center">
                            {{ formatTrendLabel(item.date) }}
                        </span>
                    </div>
                </div>
                <div v-else class="py-12 text-center text-sm text-text-tertiary">
                    Hakuna miamala iliyorekodiwa kwenye kipindi hiki.
                </div>
            </div>
        </Card>

        <!-- 3 Insight Cards (Payment Methods, Cashiers, Order Status) -->
        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Payment Methods -->
            <Card :padded="false" class="overflow-hidden">
                <div class="border-b border-border-subtle p-4 sm:px-5">
                    <h3 class="text-sm font-bold text-text-primary flex items-center gap-2">
                        <CreditCard :size="16" class="text-emerald-600" />
                        Njia za Malipo Zilizotumika
                    </h3>
                </div>
                <div class="p-4 space-y-3">
                    <div v-if="paymentMethods && paymentMethods.length > 0" class="space-y-3">
                        <div v-for="pm in paymentMethods" :key="pm.method" class="space-y-1">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-medium text-text-primary">{{ paymentMethodLabel(pm.method) }}</span>
                                <span class="font-bold text-text-primary tabular-nums">{{ formatCurrency(pm.revenue) }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="h-2 flex-1 rounded-full bg-surface-overlay overflow-hidden">
                                    <div
                                        class="h-full bg-emerald-500 rounded-full"
                                        :style="{ width: `${summary.total_revenue > 0 ? (pm.revenue / summary.total_revenue) * 100 : 0}%` }"
                                    ></div>
                                </div>
                                <span class="text-[11px] text-text-tertiary shrink-0 tabular-nums">
                                    {{ summary.total_revenue > 0 ? Math.round((pm.revenue / summary.total_revenue) * 100) : 0 }}% ({{ pm.count }})
                                </span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="py-6 text-center text-xs text-text-tertiary">
                        Hakuna taarifa za malipo.
                    </div>
                </div>
            </Card>

            <!-- Cashier / User Performance -->
            <Card :padded="false" class="overflow-hidden">
                <div class="border-b border-border-subtle p-4 sm:px-5">
                    <h3 class="text-sm font-bold text-text-primary flex items-center gap-2">
                        <Users :size="16" class="text-brand" />
                        Mauzo kwa Wauzaji / Kaunta
                    </h3>
                </div>
                <div class="p-4">
                    <div v-if="salesByUser && salesByUser.length > 0" class="divide-y divide-border-subtle">
                        <div v-for="staff in salesByUser" :key="staff.user_name" class="flex items-center justify-between py-2.5 first:pt-0 last:pb-0">
                            <div class="flex items-center gap-2.5">
                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-brand/10 text-brand font-bold text-xs">
                                    {{ staff.user_name.charAt(0).toUpperCase() }}
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-text-primary">{{ staff.user_name }}</p>
                                    <p class="text-[11px] text-text-tertiary">{{ staff.orders_count }} mauzo</p>
                                </div>
                            </div>
                            <span class="text-xs font-bold text-text-primary tabular-nums">
                                {{ formatCurrency(staff.total_revenue) }}
                            </span>
                        </div>
                    </div>
                    <div v-else class="py-6 text-center text-xs text-text-tertiary">
                        Hakuna taarifa za wauzaji.
                    </div>
                </div>
            </Card>

            <!-- Order Status Distribution -->
            <Card :padded="false" class="overflow-hidden">
                <div class="border-b border-border-subtle p-4 sm:px-5">
                    <h3 class="text-sm font-bold text-text-primary flex items-center gap-2">
                        <CheckCircle2 :size="16" class="text-purple-600" />
                        Hali ya Miamala
                    </h3>
                </div>
                <div class="p-4">
                    <div v-if="byStatus && byStatus.length > 0" class="space-y-3">
                        <div v-for="st in byStatus" :key="st.status" class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <Badge :variant="statusVariant(st.status)" size="sm" dot>
                                    {{ statusLabel(st.status) }}
                                </Badge>
                                <span class="text-xs text-text-tertiary">({{ st.count }} oda)</span>
                            </div>
                            <span class="text-xs font-bold text-text-primary tabular-nums">
                                {{ formatCurrency(st.revenue) }}
                            </span>
                        </div>
                    </div>
                    <div v-else class="py-6 text-center text-xs text-text-tertiary">
                        Hakuna taarifa za hali ya oda.
                    </div>
                </div>
            </Card>
        </div>

        <!-- Top Selling & Most Profitable Products Table -->
        <Card class="mt-6 overflow-hidden" :padded="false">
            <div class="border-b border-border-subtle p-4 sm:px-6">
                <h3 class="text-base font-bold text-text-primary flex items-center gap-2">
                    <Boxes :size="18" class="text-emerald-600" />
                    Bidhaa Zinazotembea Zaidi & Zenye Faida Kubwa
                </h3>
                <p class="text-xs text-text-tertiary mt-0.5">Orodha ya bidhaa zilizouzwa zaidi na kiasi cha faida kilichopatikana</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr>
                            <th :class="thClass">Bidhaa</th>
                            <th :class="thClass">SKU</th>
                            <th :class="[thClass, 'text-center']">Vipande Vilivyouzwa</th>
                            <th :class="[thClass, 'text-right']">Mapato ya Mauzo</th>
                            <th :class="[thClass, 'text-right']">Gharama za Stoo</th>
                            <th :class="[thClass, 'text-right']">Faida Halisi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-subtle">
                        <tr v-for="prod in topProducts" :key="prod.sku" class="transition-colors hover:bg-surface-overlay/50">
                            <td class="px-4 py-3.5 font-medium text-text-primary">{{ prod.product_name }}</td>
                            <td class="px-4 py-3.5 text-xs text-text-tertiary font-mono">{{ prod.sku }}</td>
                            <td class="px-4 py-3.5 text-center font-semibold text-text-primary tabular-nums">{{ prod.quantity_sold }}</td>
                            <td class="px-4 py-3.5 text-right font-semibold text-emerald-600 dark:text-emerald-400 tabular-nums">
                                {{ formatCurrency(prod.revenue) }}
                            </td>
                            <td class="px-4 py-3.5 text-right text-text-secondary tabular-nums">
                                {{ formatCurrency(prod.cost) }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-bold text-blue-600 dark:text-blue-400 tabular-nums">
                                {{ formatCurrency(prod.profit) }}
                            </td>
                        </tr>
                        <tr v-if="!topProducts || topProducts.length === 0">
                            <td colspan="6" class="py-8 text-center text-xs text-text-tertiary">
                                Hakuna mauzo ya bidhaa yaliyorekodiwa kwenye kipindi hiki.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Card>

        <!-- Detailed Period Breakdown Table -->
        <Card class="mt-6 overflow-hidden" :padded="false">
            <div class="border-b border-border-subtle p-4 sm:px-6">
                <h3 class="text-base font-bold text-text-primary flex items-center gap-2">
                    <Clock :size="18" class="text-brand" />
                    Mchanganuo Kamili wa Mauzo ({{ filters?.period_label }})
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr>
                            <th :class="thClass">{{ filters?.granularity === 'hour' ? 'Saa' : filters?.granularity === 'month' ? 'Mwezi' : 'Tarehe' }}</th>
                            <th :class="[thClass, 'text-center']">Idadi ya Mauzo</th>
                            <th :class="[thClass, 'text-right']">Jumla ya Mapato</th>
                            <th :class="[thClass, 'text-right']">Mgao wa Jumla</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-subtle">
                        <tr v-for="row in dailySales" :key="row.date" class="transition-colors hover:bg-surface-overlay/50">
                            <td class="px-4 py-3 font-medium text-text-primary">{{ formatTrendLabel(row.date) }}</td>
                            <td class="px-4 py-3 text-center text-text-secondary tabular-nums">{{ row.orders }}</td>
                            <td class="px-4 py-3 text-right font-bold text-emerald-600 dark:text-emerald-400 tabular-nums">
                                {{ formatCurrency(row.revenue) }}
                            </td>
                            <td class="px-4 py-3 text-right text-xs text-text-tertiary tabular-nums">
                                {{ summary.total_revenue > 0 ? Math.round((row.revenue / summary.total_revenue) * 100) : 0 }}%
                            </td>
                        </tr>
                        <tr v-if="!dailySales || dailySales.length === 0">
                            <td colspan="4" class="py-8 text-center text-xs text-text-tertiary">
                                Hakuna taarifa kwenye kipindi hiki.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Card>
    </AppLayout>
</template>

<style scoped>
@media print {
    /* Hide navigation, sidebar, top buttons when printing */
    header, nav, aside, button, .print\:hidden {
        display: none !important;
    }
    body, main {
        background: #ffffff !important;
        color: #000000 !important;
        padding: 0 !important;
        margin: 0 !important;
    }
}
</style>
