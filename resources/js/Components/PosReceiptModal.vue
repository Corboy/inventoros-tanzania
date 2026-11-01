<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useCurrency } from '@/composables/useCurrency';
import { Printer, X } from 'lucide-vue-next';
import Button from '@/Components/ui/Button.vue';

const { t } = useI18n();
const { formatCurrency } = useCurrency();

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    order: {
        type: Object,
        required: true,
    },
    organization: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(['close']);

const paymentMethodLabel = computed(() => {
    const method = props.order?.metadata?.payment_method || 'cash';
    const map = {
        cash: 'Pesa Taslimu (Cash)',
        mpesa: 'M-Pesa / TigoPesa / Airtel Money',
        bank: 'Benki / Kadi',
        credit: 'Deni / Mkopo (Credit)',
    };
    return map[method] || method.toUpperCase();
});

const printReceipt = () => {
    window.print();
};

const formatDate = (dateStr) => {
    if (!dateStr) return new Date().toLocaleString();
    try {
        const d = new Date(dateStr);
        return d.toLocaleDateString('sw-TZ', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch (e) {
        return dateStr;
    }
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm">
        <div class="relative flex max-h-[90vh] w-full max-w-md flex-col rounded-xl bg-surface-canvas shadow-2xl overflow-hidden border border-border-subtle">
            <div class="flex items-center justify-between border-b border-border-subtle px-5 py-4 bg-surface-subtle print:hidden">
                <div class="flex items-center gap-2">
                    <Printer class="h-5 w-5 text-brand" />
                    <h3 class="font-semibold text-text-primary">Risiti ya POS (POS Receipt)</h3>
                </div>
                <button
                    type="button"
                    @click="emit('close')"
                    class="rounded-lg p-1.5 text-text-tertiary hover:bg-surface-sunken hover:text-text-primary"
                >
                    <X class="h-5 w-5" />
                </button>
            </div>

            <div class="overflow-y-auto p-6 bg-white print:p-0 print:m-0 print:overflow-visible">
                <div id="thermal-receipt-area" class="thermal-receipt mx-auto font-mono text-black text-xs leading-relaxed max-w-[320px] bg-white p-4 border border-dashed border-gray-300 print:border-none print:max-w-full print:p-0">
                    <div class="text-center pb-3 border-b border-dashed border-black space-y-0.5">
                        <div class="text-base font-bold uppercase tracking-wider">{{ organization?.name || 'DUKA LETU' }}</div>
                        <div v-if="organization?.address" class="text-[11px] text-gray-700">{{ organization.address }}</div>
                        <div v-if="organization?.phone" class="text-[11px] text-gray-700">Simu: {{ organization.phone }}</div>
                        <div v-if="organization?.email" class="text-[11px] text-gray-700">{{ organization.email }}</div>
                        <div v-if="order?.metadata?.customer_tin" class="text-[11px] font-semibold text-gray-800">TIN: {{ order.metadata.customer_tin }}</div>
                    </div>

                    <div class="py-2.5 border-b border-dashed border-black space-y-1 text-[11px]">
                        <div class="flex justify-between">
                            <span>Risiti #:</span>
                            <span class="font-bold">{{ order.order_number }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Tarehe:</span>
                            <span>{{ formatDate(order.order_date || order.created_at) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Mteja:</span>
                            <span class="font-semibold">{{ order.customer_name || 'Mteja wa Kawaida' }}</span>
                        </div>
                        <div v-if="order?.metadata?.customer_phone" class="flex justify-between">
                            <span>Simu ya Mteja:</span>
                            <span>{{ order.metadata.customer_phone }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Malipo:</span>
                            <span class="font-semibold">{{ paymentMethodLabel }}</span>
                        </div>
                    </div>

                    <div class="py-2.5 border-b border-dashed border-black">
                        <table class="w-full text-left text-[11px]">
                            <thead>
                                <tr class="border-b border-gray-400 pb-1">
                                    <th class="py-1">Bidhaa (Item)</th>
                                    <th class="text-center py-1">Idadi</th>
                                    <th class="text-right py-1">Jumla</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="item in order.items" :key="item.id">
                                    <td class="py-1.5 pr-2">
                                        <div class="font-semibold">{{ item.product?.name || item.product_name || 'Bidhaa' }}</div>
                                        <div class="text-[10px] text-gray-600">@ {{ formatCurrency(item.unit_price, order.currency) }}</div>
                                    </td>
                                    <td class="text-center py-1.5 font-medium">{{ item.quantity }}</td>
                                    <td class="text-right py-1.5 font-semibold">{{ formatCurrency((item.quantity || 0) * (item.unit_price || 0), order.currency) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="py-2.5 border-b border-dashed border-black space-y-1.5 text-xs">
                        <div class="flex justify-between">
                            <span>Jumla Ndogo (Subtotal):</span>
                            <span>{{ formatCurrency(order.subtotal || order.total, order.currency) }}</span>
                        </div>
                        <div v-if="order.tax && order.tax > 0" class="flex justify-between">
                            <span>Kodi (VAT/Tax):</span>
                            <span>{{ formatCurrency(order.tax, order.currency) }}</span>
                        </div>
                        <div v-if="order.shipping && order.shipping > 0" class="flex justify-between">
                            <span>Usafirishaji (Shipping):</span>
                            <span>{{ formatCurrency(order.shipping, order.currency) }}</span>
                        </div>
                        <div class="flex justify-between items-center text-sm font-bold pt-1 border-t border-black">
                            <span>JUMLA KUU (TOTAL):</span>
                            <span class="text-base">{{ formatCurrency(order.total, order.currency) }}</span>
                        </div>
                        <div v-if="order?.metadata?.cash_tendered" class="flex justify-between text-[11px] pt-1 border-t border-dashed border-gray-400">
                            <span>Pesa Iliyopokelewa:</span>
                            <span class="font-semibold">{{ formatCurrency(order.metadata.cash_tendered, order.currency) }}</span>
                        </div>
                        <div v-if="order?.metadata?.change_due !== undefined && order?.metadata?.change_due !== null" class="flex justify-between text-[11px]">
                            <span>Chenji (Change Due):</span>
                            <span class="font-bold">{{ formatCurrency(order.metadata.change_due, order.currency) }}</span>
                        </div>
                    </div>

                    <div class="text-center pt-3 space-y-1 text-[11px]">
                        <p class="font-bold uppercase tracking-wider">*** ASANTE KWA KUFANYA BIASHARA NASI ***</p>
                        <p class="text-gray-700">Bidhaa zilizouzwa hazirudishwi</p>
                        <p class="text-[10px] text-gray-500 pt-1">Imetolewa na Inventoros — Tanzania Edition</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-border-subtle bg-surface-subtle p-4 print:hidden">
                <Button variant="secondary" @click="emit('close')">
                    {{ t('common.cancel') || 'Funga' }}
                </Button>
                <Button variant="default" @click="printReceipt" class="gap-2 bg-brand text-white font-semibold">
                    <Printer :size="16" />
                    Chapisha Risiti (Print POS)
                </Button>
            </div>
        </div>
    </div>
</template>

<style>
@media print {
    body * {
        visibility: hidden !important;
    }
    #thermal-receipt-area, #thermal-receipt-area * {
        visibility: visible !important;
    }
    #thermal-receipt-area {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        max-width: 80mm !important;
        margin: 0 !important;
        padding: 4mm !important;
        box-shadow: none !important;
        border: none !important;
        font-family: 'Courier New', Courier, monospace !important;
        color: #000 !important;
        background: #fff !important;
    }
    @page {
        size: 80mm auto;
        margin: 0;
    }
}
</style>
