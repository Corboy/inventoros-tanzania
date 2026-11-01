<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import Card from '@/Components/ui/Card.vue';
import Button from '@/Components/ui/Button.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed, nextTick, onMounted, onUnmounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { useCurrency } from '@/composables/useCurrency';
import {
    ArrowLeft,
    Plus,
    Minus,
    Trash2,
    PackageOpen,
    Search,
    User,
    UserCheck,
    CheckCircle2,
    Zap,
    CreditCard,
    Smartphone,
    Banknote,
    FileText,
    Sparkles,
    ShoppingBag,
} from 'lucide-vue-next';

const { t } = useI18n();
const { formatCurrency, getCurrencySymbol } = useCurrency();

const props = defineProps({
    products: Array,
});

// Customer Type: 'walkin' or 'custom'
const isWalkIn = ref(true);
const paymentMethod = ref('cash');

const form = useForm({
    customer_name: 'Walk-in Customer',
    customer_email: '',
    customer_phone: '',
    customer_address: '',
    customer_tin: '',
    status: 'delivered',
    payment_method: 'cash',
    order_date: new Date().toISOString().split('T')[0],
    shipping: 0,
    tax: 0,
    notes: '',
    items: [],
    cash_tendered: null,
    change_due: 0,
});

const cashTendered = ref(null);
const changeDue = computed(() => {
    if (!cashTendered.value || cashTendered.value <= total.value) return 0;
    return cashTendered.value - total.value;
});
const cashShortage = computed(() => {
    if (!cashTendered.value || paymentMethod.value !== 'cash') return 0;
    if (cashTendered.value < total.value) {
        return total.value - cashTendered.value;
    }
    return 0;
});

const setExactCash = () => {
    cashTendered.value = total.value;
};

const setQuickCash = (amount) => {
    cashTendered.value = amount;
};

const searchQuery = ref('');
const searchInputRef = ref(null);
const quantity = ref(1);
const selectedProduct = ref(null);

const toggleCustomerType = (walkIn) => {
    isWalkIn.value = walkIn;
    if (walkIn) {
        form.customer_name = 'Walk-in Customer';
        form.customer_email = '';
        form.customer_phone = '';
        form.customer_address = '';
    } else {
        form.customer_name = '';
    }
};

const setPaymentMethod = (method) => {
    paymentMethod.value = method;
    form.payment_method = method;
    if (method === 'credit' && isWalkIn.value) {
        // If credit sale, prompt for customer name
        toggleCustomerType(false);
    }
};

// Filtered products for quick search
const searchResults = computed(() => {
    const query = searchQuery.value.trim().toLowerCase();
    if (!query) return [];
    return props.products
        .filter(p => p.stock > 0 && (
            p.name.toLowerCase().includes(query) ||
            (p.sku && p.sku.toLowerCase().includes(query)) ||
            (p.barcode && p.barcode.toLowerCase().includes(query))
        ))
        .slice(0, 8);
});

// Quick add product from search
const addProductDirectly = (product) => {
    if (!product) return;
    const existingIndex = form.items.findIndex(item => item.product_id === product.id);
    if (existingIndex >= 0) {
        form.items[existingIndex].quantity += 1;
    } else {
        form.items.push({
            product_id: product.id,
            product_name: product.name,
            sku: product.sku,
            quantity: 1,
            unit_price: parseFloat(product.price),
            retail_price: parseFloat(product.price),
            wholesale_price: product.wholesale_price ? parseFloat(product.wholesale_price) : null,
            price_type: 'retail',
            unit: product.unit || 'Pcs',
        });
    }
    searchQuery.value = '';
    nextTick(() => {
        searchInputRef.value?.focus();
    });
};

const togglePriceType = (index) => {
    const item = form.items[index];
    if (!item.wholesale_price) return;
    if (item.price_type === 'wholesale') {
        item.price_type = 'retail';
        item.unit_price = item.retail_price;
    } else {
        item.price_type = 'wholesale';
        item.unit_price = item.wholesale_price;
    }
};

const handleSearchKeydown = (e) => {
    if (e.key === 'Enter') {
        e.preventDefault();
        if (searchResults.value.length > 0) {
            addProductDirectly(searchResults.value[0]);
        }
    }
};

const addItem = () => {
    if (!selectedProduct.value || quantity.value < 1) return;
    const product = props.products.find(p => p.id === selectedProduct.value);
    if (!product) return;
    const existingIndex = form.items.findIndex(item => item.product_id === product.id);
    if (existingIndex >= 0) {
        form.items[existingIndex].quantity += quantity.value;
    } else {
        form.items.push({
            product_id: product.id,
            product_name: product.name,
            sku: product.sku,
            quantity: quantity.value,
            unit_price: parseFloat(product.price),
            retail_price: parseFloat(product.price),
            wholesale_price: product.wholesale_price ? parseFloat(product.wholesale_price) : null,
            price_type: 'retail',
            unit: product.unit || 'Pcs',
        });
    }
    selectedProduct.value = null;
    quantity.value = 1;
};

const quickProducts = computed(() => {
    return (props.products || [])
        .filter(p => p.stock > 0)
        .slice(0, 8);
});

const incrementQuantity = (index) => {
    const current = parseInt(form.items[index].quantity, 10) || 0;
    form.items[index].quantity = current + 1;
};

const decrementQuantity = (index) => {
    const current = parseInt(form.items[index].quantity, 10) || 1;
    if (current > 1) {
        form.items[index].quantity = current - 1;
    } else {
        removeItem(index);
    }
};

const handleGlobalKeydown = (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        e.preventDefault();
        submit();
    }
};

onMounted(() => {
    window.addEventListener('keydown', handleGlobalKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleGlobalKeydown);
});

const removeItem = (index) => {
    form.items.splice(index, 1);
};

const updateItemQuantity = (index, rawValue) => {
    if (rawValue === '' || rawValue === null || rawValue === undefined) {
        form.items[index].quantity = '';
        return;
    }
    const parsed = parseInt(rawValue, 10);
    form.items[index].quantity = isNaN(parsed) ? '' : Math.max(0, parsed);
};

const normalizeItemQuantity = (index) => {
    const current = parseInt(form.items[index].quantity, 10);
    if (isNaN(current) || current < 1) {
        form.items[index].quantity = 1;
    } else {
        form.items[index].quantity = current;
    }
};

const updateItemPrice = (index, newPrice) => {
    form.items[index].unit_price = parseFloat(newPrice) || 0;
};

const subtotal = computed(() => form.items.reduce((sum, item) => sum + ((parseInt(item.quantity, 10) || 0) * item.unit_price), 0));
const total = computed(() => subtotal.value + parseFloat(form.tax || 0) + parseFloat(form.shipping || 0));

const submit = () => {
    if (form.items.length === 0) {
        alert(t('orders.create.noItemsAlert') || 'Tafadhali ongeza angalau bidhaa moja kwenye mauzo.');
        return;
    }
    // Ensure all item quantities are at least 1 before submitting
    form.items.forEach(item => {
        item.quantity = Math.max(1, parseInt(item.quantity, 10) || 1);
    });
    if (paymentMethod.value === 'cash' && cashShortage.value > 0) {
        alert(`Pesa iliyotolewa na mteja haitoshi! Imepungua ${formatCurrency(cashShortage.value)}. Tafadhali rekebisha kiasi kabla ya kukamilisha.`);
        return;
    }
    if (!isWalkIn.value && !form.customer_name.trim()) {
        form.customer_name = 'Walk-in Customer';
    }
    form.cash_tendered = cashTendered.value;
    form.change_due = changeDue.value;
    form.post(route('orders.store'), { preserveScroll: true });
};

const availableProducts = computed(() =>
    [...props.products]
        .filter(p => p.stock > 0)
        .sort((a, b) => a.name.localeCompare(b.name, undefined, { sensitivity: 'base' }))
);

const fieldLabel = 'mb-1 block text-sm font-medium text-text-secondary';
const fieldInput = 'h-9 w-full rounded-md border border-border-subtle bg-surface-canvas px-3 text-sm text-text-primary placeholder:text-text-tertiary ds-focus-ring';
const fieldArea = 'w-full rounded-md border border-border-subtle bg-surface-canvas px-3 py-2 text-sm text-text-primary placeholder:text-text-tertiary ds-focus-ring';
const fieldError = 'mt-1 text-xs text-status-danger';
</script>

<template>
    <Head :title="t('orders.create.title')" />

    <AppLayout>
        <template #header>
            <div class="flex items-center gap-2 text-xs">
                <Link :href="route('orders.index')" class="text-text-tertiary hover:text-text-primary">{{ t('nav.dashboard') }}</Link>
                <span class="text-text-tertiary">/</span>
                <Link :href="route('orders.index')" class="text-text-tertiary hover:text-text-primary">{{ t('orders.title') }}</Link>
                <span class="text-text-tertiary">/</span>
                <span class="font-medium text-text-primary">{{ t('orders.create.title') }}</span>
            </div>
        </template>

        <PageHeader :title="t('orders.create.title')" description="Fanya mauzo ya haraka ya dukani au tengeneza oda ya mteja.">
            <template #actions>
                <Button variant="secondary" size="sm" as="Link" :href="route('orders.index')">
                    <ArrowLeft :size="14" />
                    {{ t('orders.create.backToOrders') }}
                </Button>
            </template>
        </PageHeader>

        <form @submit.prevent="submit" class="mt-6">
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                <!-- Left 2 columns: Product addition, Quick tap, and items cart -->
                <div class="space-y-5 lg:col-span-2">
                    
                    <!-- Fast Product Search & Quick Tap POS Bar -->
                    <Card :padded="false">
                        <div class="p-4 bg-brand/5 border-b border-border-subtle flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <Zap :size="18" class="text-brand" />
                                <h3 class="text-sm font-semibold text-text-primary">{{ t('orders.create.selectProduct') }}</h3>
                            </div>
                            <span class="text-xs text-text-tertiary">Tafuta kwa Jina, SKU au Skana Barcode (Bonyeza Enter)</span>
                        </div>
                        <div class="p-4 space-y-3">
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <Search :size="16" class="text-text-tertiary" />
                                </div>
                                <input
                                    ref="searchInputRef"
                                    v-model="searchQuery"
                                    @keydown="handleSearchKeydown"
                                    type="text"
                                    placeholder="Andika jina la bidhaa, SKU, au skana barcode..."
                                    class="h-11 w-full rounded-lg border border-brand/40 bg-surface-canvas pl-10 pr-4 text-sm text-text-primary placeholder:text-text-tertiary focus:border-brand focus:ring-2 focus:ring-brand/20 shadow-sm"
                                    autofocus
                                />
                                
                                <!-- Instant Search Dropdown Results -->
                                <div v-if="searchResults.length > 0" class="absolute left-0 right-0 top-full mt-1 z-30 bg-surface-base border border-border-subtle rounded-lg shadow-xl overflow-hidden divide-y divide-border-subtle">
                                    <div
                                        v-for="p in searchResults"
                                        :key="p.id"
                                        @click="addProductDirectly(p)"
                                        class="flex items-center justify-between p-3 hover:bg-brand/10 cursor-pointer transition-colors"
                                    >
                                        <div>
                                            <p class="text-sm font-medium text-text-primary">{{ p.name }}</p>
                                            <p class="text-xs text-text-tertiary">
                                                SKU: {{ p.sku }} | Zipo: <span class="font-semibold text-status-success">{{ p.stock }} {{ p.unit || 'Pcs' }}</span>
                                                <span v-if="p.wholesale_price" class="ml-2 text-brand font-medium">Jumla: {{ formatCurrency(p.wholesale_price) }}</span>
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-bold text-brand">{{ formatCurrency(p.price) }}</p>
                                            <span class="text-[11px] text-text-secondary bg-surface-overlay px-1.5 py-0.5 rounded font-medium">+ Ongeza</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ⚡ Quick-Tap Products Grid (Bofya mara moja kuongeza) -->
                            <div v-if="quickProducts.length > 0" class="pt-2">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-semibold text-text-secondary flex items-center gap-1.5">
                                        <Sparkles :size="13" class="text-amber-500" /> Bidhaa za Haraka Dukani:
                                    </span>
                                    <span class="text-[11px] text-text-tertiary">Gusa mara 1 kuweka kwenye kikapu</span>
                                </div>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                    <button
                                        v-for="p in quickProducts"
                                        :key="p.id"
                                        type="button"
                                        @click="addProductDirectly(p)"
                                        class="group p-2.5 rounded-lg border border-border-subtle bg-surface-canvas hover:border-brand hover:bg-brand/5 hover:shadow-xs transition-all text-left flex flex-col justify-between"
                                    >
                                        <div>
                                            <p class="text-xs font-semibold text-text-primary truncate group-hover:text-brand transition-colors">{{ p.name }}</p>
                                            <p class="text-[10px] text-text-tertiary mt-0.5">Stoo: <span class="font-medium text-status-success">{{ p.stock }} {{ p.unit || 'Pcs' }}</span></p>
                                        </div>
                                        <div class="mt-2 flex items-center justify-between">
                                            <span class="text-xs font-bold text-brand">{{ formatCurrency(p.price) }}</span>
                                            <span class="text-[10px] px-1 rounded bg-brand/10 text-brand font-medium group-hover:bg-brand group-hover:text-white transition-colors">+ Weka</span>
                                        </div>
                                    </button>
                                </div>
                            </div>

                            <!-- Manual Selector Dropdown (Kama orodha ni ndefu) -->
                            <details class="text-xs text-text-tertiary cursor-pointer pt-1">
                                <summary class="hover:text-text-primary">Au chagua bidhaa kutoka kwenye orodha yote...</summary>
                                <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-12">
                                    <div class="sm:col-span-8">
                                        <select v-model="selectedProduct" :class="fieldInput">
                                            <option :value="null">{{ t('orders.create.chooseProduct') }}</option>
                                            <option v-for="product in availableProducts" :key="product.id" :value="product.id">
                                                {{ product.name }} ({{ product.sku }}) - Stoo: {{ product.stock }} {{ product.unit || 'Pcs' }} - {{ formatCurrency(product.price) }}
                                            </option>
                                        </select>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <input v-model.number="quantity" type="number" min="1" placeholder="Idadi" :class="fieldInput" />
                                    </div>
                                    <div class="sm:col-span-2">
                                        <Button type="button" variant="secondary" class="w-full" @click="addItem">
                                            <Plus :size="14" /> {{ t('common.add') }}
                                        </Button>
                                    </div>
                                </div>
                            </details>
                        </div>
                    </Card>

                    <!-- Order items table / Cart -->
                    <Card :padded="false">
                        <div class="px-5 pt-4 pb-3 border-b border-border-subtle flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <ShoppingBag :size="16" class="text-brand" />
                                <h3 class="text-sm font-semibold text-text-primary">{{ t('orders.create.orderItems') }}</h3>
                            </div>
                            <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-brand/10 text-brand">{{ form.items.length }} bidhaa zilizopo</span>
                        </div>
                        <div class="p-4 sm:p-5">
                            <div v-if="form.items.length > 0" class="space-y-3">
                                <div v-for="(item, index) in form.items" :key="index" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 rounded-lg border border-border-subtle bg-surface-canvas p-3 hover:border-border-strong transition-colors">
                                    <!-- Product Info & Wholesale toggle -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <p class="font-semibold text-sm text-text-primary">{{ item.product_name }}</p>
                                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-surface-sunken text-text-secondary font-mono border border-border-subtle">{{ item.unit || 'Pcs' }}</span>
                                        </div>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="text-xs text-text-tertiary">SKU: {{ item.sku }}</span>
                                            <button
                                                v-if="item.wholesale_price"
                                                type="button"
                                                @click="togglePriceType(index)"
                                                :class="[
                                                    'text-[10px] font-bold px-2 py-0.5 rounded border transition-colors',
                                                    item.price_type === 'wholesale'
                                                        ? 'bg-status-success/15 text-status-success border-status-success/40'
                                                        : 'bg-surface-sunken text-text-secondary border-border-subtle hover:text-text-primary'
                                                ]"
                                            >
                                                {{ item.price_type === 'wholesale' ? '🏷️ Bei ya Jumla' : '🛒 Bei ya Rejareja' }}
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Stepper, Unit Price, Line Total, Trash -->
                                    <div class="flex items-center justify-between sm:justify-end gap-3 shrink-0">
                                        <!-- Quantity Stepper (- [Qty] +) -->
                                        <div>
                                            <label class="mb-1 block text-[10px] uppercase font-semibold text-text-tertiary">{{ t('common.quantity') }}</label>
                                            <div class="flex items-center rounded-md border border-border-subtle bg-surface-canvas overflow-hidden shadow-2xs">
                                                <button
                                                    type="button"
                                                    @click="decrementQuantity(index)"
                                                    class="h-8 w-8 flex items-center justify-center bg-surface-sunken hover:bg-surface-overlay text-text-secondary active:bg-brand/10 transition-colors"
                                                    title="Punguza"
                                                >
                                                    <Minus :size="13" />
                                                </button>
                                                <input
                                                    :value="item.quantity"
                                                    @input="updateItemQuantity(index, $event.target.value)"
                                                    @blur="normalizeItemQuantity(index)"
                                                    @keydown.enter.prevent="$event.target.blur()"
                                                    type="number"
                                                    min="1"
                                                    class="h-8 w-16 px-1 border-x border-border-subtle bg-transparent text-center text-sm font-bold text-text-primary focus:outline-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                                />
                                                <button
                                                    type="button"
                                                    @click="incrementQuantity(index)"
                                                    class="h-8 w-8 flex items-center justify-center bg-surface-sunken hover:bg-surface-overlay text-text-secondary active:bg-brand/10 transition-colors"
                                                    title="Ongeza"
                                                >
                                                    <Plus :size="13" />
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Unit Price -->
                                        <div class="w-24">
                                            <label class="mb-1 block text-[10px] uppercase font-semibold text-text-tertiary">{{ t('common.price') }}</label>
                                            <input
                                                :value="item.unit_price"
                                                @input="updateItemPrice(index, $event.target.value)"
                                                type="number"
                                                step="1"
                                                min="0"
                                                class="h-8 w-full rounded border border-border-subtle bg-surface-canvas px-2 text-right text-xs font-medium text-text-primary [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                            />
                                        </div>

                                        <!-- Subtotal -->
                                        <div class="w-24 text-right">
                                            <label class="mb-1 block text-[10px] uppercase font-semibold text-text-tertiary">{{ t('common.total') }}</label>
                                            <p class="font-bold tabular-nums text-text-primary text-sm leading-8">{{ formatCurrency((parseInt(item.quantity, 10) || 0) * item.unit_price) }}</p>
                                        </div>

                                        <!-- Remove Item -->
                                        <button
                                            type="button"
                                            @click="removeItem(index)"
                                            class="mt-4 rounded-md p-1.5 text-text-tertiary transition-colors hover:bg-status-danger/10 hover:text-status-danger"
                                            title="Ondoa bidhaa hii"
                                        >
                                            <Trash2 :size="16" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="flex flex-col items-center justify-center py-12 text-center">
                                <PackageOpen :size="36" class="text-text-tertiary mb-2 opacity-50" />
                                <p class="text-sm font-semibold text-text-secondary">Kikapu cha mauzo kipo wazi</p>
                                <p class="text-xs text-text-tertiary mt-1">Gusa bidhaa kwenye orodha hapo juu au andika jina/skana barcode kuanza mauzo</p>
                            </div>
                            <p v-if="form.errors.items" :class="fieldError">{{ form.errors.items }}</p>
                        </div>
                    </Card>

                    <!-- Customer Info (Simple / Walk-in Default) -->
                    <Card :padded="false">
                        <div class="px-5 pt-4 pb-3 border-b border-border-subtle flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-text-primary">{{ t('orders.create.customerInfo') }}</h3>
                            <div class="flex items-center gap-1 bg-surface-sunken p-0.5 rounded-lg border border-border-subtle text-xs">
                                <button
                                    type="button"
                                    @click="toggleCustomerType(true)"
                                    :class="[
                                        'px-2.5 py-1 rounded-md font-medium transition-colors',
                                        isWalkIn ? 'bg-surface-base text-text-primary shadow-xs font-semibold' : 'text-text-tertiary hover:text-text-primary'
                                    ]"
                                >
                                    {{ t('orders.create.walkInCustomer') }}
                                </button>
                                <button
                                    type="button"
                                    @click="toggleCustomerType(false)"
                                    :class="[
                                        'px-2.5 py-1 rounded-md font-medium transition-colors',
                                        !isWalkIn ? 'bg-surface-base text-text-primary shadow-xs font-semibold' : 'text-text-tertiary hover:text-text-primary'
                                    ]"
                                >
                                    Mteja Maalum
                                </button>
                            </div>
                        </div>

                        <div class="p-4 sm:p-5">
                            <!-- Walk-in Banner -->
                            <div v-if="isWalkIn" class="flex items-center gap-3 p-3 rounded-lg bg-surface-canvas border border-border-subtle text-xs text-text-secondary">
                                <UserCheck :size="18" class="text-brand shrink-0" />
                                <div>
                                    <p class="font-medium text-text-primary">Mauzo ya Mteja wa Kawaida (Walk-in Customer)</p>
                                    <p class="text-text-tertiary">Hakuna taarifa za ziada zinazohitajika. Bonyeza "Mteja Maalum" kama unataka kurekodi jina, simu, au kutoa ankara (invoice).</p>
                                </div>
                            </div>

                            <!-- Detailed Customer Form -->
                            <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="customer_name" :class="fieldLabel">{{ t('orders.create.customerName') }} <span class="text-status-danger">*</span></label>
                                    <input id="customer_name" v-model="form.customer_name" type="text" placeholder="Jina la Mteja au Kampuni" :class="fieldInput" required />
                                    <p v-if="form.errors.customer_name" :class="fieldError">{{ form.errors.customer_name }}</p>
                                </div>
                                <div>
                                    <label for="customer_phone" :class="fieldLabel">Nambari ya Simu</label>
                                    <input id="customer_phone" v-model="form.customer_phone" type="tel" placeholder="07XXXXXXXX / +255..." :class="fieldInput" />
                                </div>
                                <div>
                                    <label for="customer_email" :class="fieldLabel">{{ t('orders.create.customerEmail') }}</label>
                                    <input id="customer_email" v-model="form.customer_email" type="email" placeholder="mteja@barua.com" :class="fieldInput" />
                                </div>
                                <div>
                                    <label for="customer_tin" :class="fieldLabel">TIN (Kama ni Kampuni)</label>
                                    <input id="customer_tin" v-model="form.customer_tin" type="text" placeholder="XXX-XXX-XXX" :class="fieldInput" />
                                </div>
                                <div class="sm:col-span-2">
                                    <label for="customer_address" :class="fieldLabel">{{ t('orders.create.shippingAddress') }}</label>
                                    <textarea id="customer_address" v-model="form.customer_address" rows="2" placeholder="Eneo au Anwani..." :class="fieldArea"></textarea>
                                </div>
                            </div>
                        </div>
                    </Card>
                </div>

                <!-- Right Column: Grand Total, Payment & Instant Checkout -->
                <div class="space-y-5">
                    <!-- Grand Total & Payment Card (Main Focus of Cashier) -->
                    <Card :padded="false" class="border-2 border-brand/30 shadow-md">
                        <!-- Big Total Header -->
                        <div class="p-5 bg-surface-canvas border-b border-border-subtle text-center">
                            <span class="text-xs uppercase font-extrabold tracking-wider text-text-tertiary">Jumla Kuu ya Malipo</span>
                            <div class="text-3xl sm:text-4xl font-black text-brand tracking-tight tabular-nums mt-1">
                                {{ formatCurrency(total) }}
                            </div>
                            <p class="text-xs text-text-secondary mt-1">
                                {{ form.items.length }} {{ form.items.length === 1 ? 'bidhaa' : 'bidhaa mbalimbali' }}
                                <span v-if="form.tax > 0 || form.shipping > 0" class="text-text-tertiary font-mono">
                                    (Subtotal: {{ formatCurrency(subtotal) }})
                                </span>
                            </p>
                        </div>

                        <!-- Payment Methods Selector -->
                        <div class="p-4 border-b border-border-subtle">
                            <label class="block text-xs font-semibold text-text-secondary mb-2.5">
                                {{ t('orders.create.paymentMethod') }}
                            </label>
                            <div class="grid grid-cols-2 gap-2">
                                <button
                                    type="button"
                                    @click="setPaymentMethod('cash')"
                                    :class="[
                                        'flex flex-col items-center justify-center p-2.5 rounded-lg border text-xs font-semibold transition-all',
                                        paymentMethod === 'cash'
                                            ? 'border-brand bg-brand/10 text-brand shadow-xs ring-1 ring-brand'
                                            : 'border-border-subtle bg-surface-base text-text-secondary hover:border-border-strong'
                                    ]"
                                >
                                    <Banknote :size="18" class="mb-1" />
                                    <span>Pesa Taslimu</span>
                                </button>

                                <button
                                    type="button"
                                    @click="setPaymentMethod('mpesa')"
                                    :class="[
                                        'flex flex-col items-center justify-center p-2.5 rounded-lg border text-xs font-semibold transition-all',
                                        paymentMethod === 'mpesa'
                                            ? 'border-status-success bg-status-success/10 text-status-success shadow-xs ring-1 ring-status-success'
                                            : 'border-border-subtle bg-surface-base text-text-secondary hover:border-border-strong'
                                    ]"
                                >
                                    <Smartphone :size="18" class="mb-1" />
                                    <span>M-Pesa / Tigo / Airtel</span>
                                </button>

                                <button
                                    type="button"
                                    @click="setPaymentMethod('bank')"
                                    :class="[
                                        'flex flex-col items-center justify-center p-2.5 rounded-lg border text-xs font-semibold transition-all',
                                        paymentMethod === 'bank'
                                            ? 'border-brand bg-brand/10 text-brand shadow-xs ring-1 ring-brand'
                                            : 'border-border-subtle bg-surface-base text-text-secondary hover:border-border-strong'
                                    ]"
                                >
                                    <CreditCard :size="18" class="mb-1" />
                                    <span>Benki / Kadi</span>
                                </button>

                                <button
                                    type="button"
                                    @click="setPaymentMethod('credit')"
                                    :class="[
                                        'flex flex-col items-center justify-center p-2.5 rounded-lg border text-xs font-semibold transition-all',
                                        paymentMethod === 'credit'
                                            ? 'border-status-danger bg-status-danger/10 text-status-danger shadow-xs ring-1 ring-status-danger'
                                            : 'border-border-subtle bg-surface-base text-text-secondary hover:border-border-strong'
                                    ]"
                                >
                                    <FileText :size="18" class="mb-1" />
                                    <span>Mkopo / Deni</span>
                                </button>
                            </div>
                        </div>

                        <!-- Cash Tendered, Shortage & Change Due -->
                        <div v-if="paymentMethod === 'cash'" class="p-4 space-y-3 bg-surface-canvas/50">
                            <div>
                                <label for="cash_tendered" class="block text-xs font-semibold text-text-primary mb-1">
                                    Pesa Iliyotolewa na Mteja (Cash Tendered)
                                </label>
                                <div class="relative">
                                    <input
                                        id="cash_tendered"
                                        v-model.number="cashTendered"
                                        type="number"
                                        min="0"
                                        step="500"
                                        placeholder="0"
                                        :class="[
                                            'h-10 w-full rounded-lg border bg-surface-base px-3 text-base font-bold text-right shadow-inner transition-colors',
                                            cashShortage > 0
                                                ? 'border-status-danger text-status-danger ring-2 ring-status-danger/30'
                                                : 'border-brand/50 text-text-primary focus:border-brand focus:ring-2 focus:ring-brand/20'
                                        ]"
                                    />
                                </div>
                            </div>

                            <!-- Quick Cash Shortcuts -->
                            <div class="grid grid-cols-3 gap-1.5 text-xs">
                                <button
                                    type="button"
                                    @click="setExactCash"
                                    class="py-1.5 px-2 rounded border border-border-subtle bg-surface-base hover:bg-brand/10 hover:border-brand text-text-secondary font-semibold transition-colors"
                                >
                                    Kamili
                                </button>
                                <button
                                    type="button"
                                    @click="setQuickCash(5000)"
                                    class="py-1.5 px-2 rounded border border-border-subtle bg-surface-base hover:bg-brand/10 hover:border-brand text-text-secondary font-medium transition-colors"
                                >
                                    5,000
                                </button>
                                <button
                                    type="button"
                                    @click="setQuickCash(10000)"
                                    class="py-1.5 px-2 rounded border border-border-subtle bg-surface-base hover:bg-brand/10 hover:border-brand text-text-secondary font-medium transition-colors"
                                >
                                    10,000
                                </button>
                                <button
                                    type="button"
                                    @click="setQuickCash(20000)"
                                    class="py-1.5 px-2 rounded border border-border-subtle bg-surface-base hover:bg-brand/10 hover:border-brand text-text-secondary font-medium transition-colors"
                                >
                                    20,000
                                </button>
                                <button
                                    type="button"
                                    @click="setQuickCash(50000)"
                                    class="py-1.5 px-2 rounded border border-border-subtle bg-surface-base hover:bg-brand/10 hover:border-brand text-text-secondary font-medium transition-colors"
                                >
                                    50,000
                                </button>
                                <button
                                    type="button"
                                    @click="setQuickCash(100000)"
                                    class="py-1.5 px-2 rounded border border-border-subtle bg-surface-base hover:bg-brand/10 hover:border-brand text-text-secondary font-medium transition-colors"
                                >
                                    100,000
                                </button>
                            </div>

                            <!-- Change / Deficit Display Banner (Chenji au Pesa Iliyopungua) -->
                            <div
                                v-if="cashTendered !== null && cashTendered > 0"
                                :class="[
                                    'p-3.5 rounded-lg flex items-center justify-between border-2 transition-all shadow-xs',
                                    cashShortage > 0
                                        ? 'bg-status-danger/15 border-status-danger text-status-danger'
                                        : 'bg-status-success/15 border-status-success text-status-success'
                                ]"
                            >
                                <div class="text-xs font-bold flex flex-col">
                                    <span v-if="cashShortage > 0" class="flex items-center gap-1 text-status-danger font-extrabold text-sm">
                                        ⚠️ PESA HAIJATOSHA!
                                    </span>
                                    <span v-else class="text-status-success font-semibold">
                                        Chenji ya Kumrudishia:
                                    </span>
                                    <span v-if="cashShortage > 0" class="text-[11px] font-normal text-status-danger/90 mt-0.5">
                                        Imepungua Shilingi:
                                    </span>
                                </div>
                                <div class="text-right">
                                    <div
                                        :class="[
                                            'text-xl font-black tabular-nums tracking-tight',
                                            cashShortage > 0 ? 'text-status-danger animate-pulse' : 'text-status-success'
                                        ]"
                                    >
                                        {{ formatCurrency(cashShortage > 0 ? cashShortage : changeDue) }}
                                    </div>
                                    <span v-if="cashShortage > 0" class="text-[10px] font-bold uppercase text-status-danger block">
                                        (Pungufu / Haijatosha)
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Main Big Checkout Button -->
                        <div class="p-4 bg-surface-base">
                            <Button
                                type="submit"
                                variant="default"
                                size="lg"
                                class="w-full text-base font-bold py-3.5 shadow-md flex items-center justify-center gap-2"
                                :loading="form.processing"
                                :disabled="form.processing || form.items.length === 0 || (paymentMethod === 'cash' && cashShortage > 0)"
                            >
                                <CheckCircle2 :size="20" />
                                <span v-if="form.processing">{{ t('common.saving') }}</span>
                                <span v-else-if="paymentMethod === 'cash' && cashShortage > 0">Pesa Haijatosha!</span>
                                <span v-else>Kamilisha Mauzo ({{ formatCurrency(total) }})</span>
                            </Button>
                            <p class="text-[11px] text-center text-text-tertiary mt-2">
                                Njia ya mkato: <kbd class="px-1.5 py-0.5 rounded bg-surface-sunken border border-border-subtle font-mono text-[10px]">Ctrl + Enter</kbd>
                            </p>
                        </div>
                    </Card>

                    <!-- Collapsible Advanced Details Accordion -->
                    <Card :padded="false">
                        <details class="group cursor-pointer">
                            <summary class="flex items-center justify-between p-4 font-semibold text-xs text-text-secondary hover:text-text-primary transition-colors">
                                <span class="flex items-center gap-2">
                                    <span>⚙️ Mipangilio ya Ziada (Tarehe, Kodi, Usafiri, Vidokezo)</span>
                                </span>
                                <span class="text-[11px] text-text-tertiary font-normal group-open:hidden">Fungua kubadilisha</span>
                            </summary>
                            <div class="p-4 pt-1 space-y-3 border-t border-border-subtle bg-surface-canvas/40">
                                <div>
                                    <label for="order_date" :class="fieldLabel">{{ t('orders.create.orderDate') }}</label>
                                    <input id="order_date" v-model="form.order_date" type="date" :class="fieldInput" required />
                                </div>
                                <div>
                                    <label for="status" :class="fieldLabel">{{ t('orders.create.statusLabel') }}</label>
                                    <select id="status" v-model="form.status" :class="fieldInput" required>
                                        <option value="delivered">Imekamilika (Delivered / Paid)</option>
                                        <option value="pending">{{ t('orders.status.pending') }}</option>
                                        <option value="processing">{{ t('orders.status.processing') }}</option>
                                        <option value="shipped">{{ t('orders.status.shipped') }}</option>
                                        <option value="cancelled">{{ t('orders.status.cancelled') }}</option>
                                    </select>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label for="tax" :class="fieldLabel">{{ t('common.tax') }} (Kodi)</label>
                                        <input id="tax" v-model.number="form.tax" type="number" step="1" min="0" placeholder="0" :class="fieldInput" />
                                    </div>
                                    <div>
                                        <label for="shipping" :class="fieldLabel">{{ t('common.shipping') }} (Usafirishaji)</label>
                                        <input id="shipping" v-model.number="form.shipping" type="number" step="1" min="0" placeholder="0" :class="fieldInput" />
                                    </div>
                                </div>
                                <div>
                                    <label for="notes" :class="fieldLabel">{{ t('common.notes') }}</label>
                                    <textarea id="notes" v-model="form.notes" rows="2" :class="fieldArea" placeholder="Vidokezo vya ziada vya mauzo..."></textarea>
                                </div>
                            </div>
                        </details>
                    </Card>

                    <!-- Back to orders link -->
                    <div class="text-center">
                        <Button variant="ghost" size="sm" class="text-text-tertiary hover:text-text-secondary" as="Link" :href="route('orders.index')">
                            {{ t('common.cancel') }} na Rudi kwenye Orodha
                        </Button>
                    </div>
                </div>
            </div>
        </form>
    </AppLayout>
</template>
