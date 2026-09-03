<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import Card from '@/Components/ui/Card.vue';
import Button from '@/Components/ui/Button.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed, nextTick } from 'vue';
import { useI18n } from 'vue-i18n';
import { useCurrency } from '@/composables/useCurrency';
import {
    ArrowLeft,
    Plus,
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
    FileText
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
});

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
        });
    }
    searchQuery.value = '';
    nextTick(() => {
        searchInputRef.value?.focus();
    });
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
        });
    }
    selectedProduct.value = null;
    quantity.value = 1;
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

const updateItemQuantity = (index, newQuantity) => {
    if (newQuantity < 1) {
        removeItem(index);
    } else {
        form.items[index].quantity = newQuantity;
    }
};

const updateItemPrice = (index, newPrice) => {
    form.items[index].unit_price = parseFloat(newPrice) || 0;
};

const subtotal = computed(() => form.items.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0));
const total = computed(() => subtotal.value + parseFloat(form.tax || 0) + parseFloat(form.shipping || 0));

const submit = () => {
    if (form.items.length === 0) {
        alert(t('orders.create.noItemsAlert') || 'Tafadhali ongeza angalau bidhaa moja kwenye mauzo.');
        return;
    }
    if (!isWalkIn.value && !form.customer_name.trim()) {
        form.customer_name = 'Walk-in Customer';
    }
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
                <!-- Left 2 columns: Product addition & items cart -->
                <div class="space-y-5 lg:col-span-2">
                    
                    <!-- Fast Product Search & Scan (POS Bar) -->
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
                                            <p class="text-xs text-text-tertiary">SKU: {{ p.sku }} | Zipo Stoo: <span class="font-semibold text-status-success">{{ p.stock }}</span></p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-bold text-brand">{{ formatCurrency(p.price) }}</p>
                                            <span class="text-[11px] text-text-secondary bg-surface-overlay px-1.5 py-0.5 rounded">+ Ongeza</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Manual Selector Dropdown -->
                            <details class="text-xs text-text-tertiary cursor-pointer">
                                <summary class="hover:text-text-primary">Au chagua bidhaa kutoka kwenye orodha yote...</summary>
                                <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-12">
                                    <div class="sm:col-span-8">
                                        <select v-model="selectedProduct" :class="fieldInput">
                                            <option :value="null">{{ t('orders.create.chooseProduct') }}</option>
                                            <option v-for="product in availableProducts" :key="product.id" :value="product.id">
                                                {{ product.name }} ({{ product.sku }}) - Stoo: {{ product.stock }} - {{ formatCurrency(product.price) }}
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

                    <!-- Order items table -->
                    <Card :padded="false">
                        <div class="px-5 pt-4 pb-3 border-b border-border-subtle flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-text-primary">{{ t('orders.create.orderItems') }}</h3>
                            <span class="text-xs text-text-tertiary">{{ form.items.length }} bidhaa</span>
                        </div>
                        <div class="p-5">
                            <div v-if="form.items.length > 0" class="space-y-3">
                                <div v-for="(item, index) in form.items" :key="index" class="flex flex-col sm:flex-row sm:items-center gap-3 rounded-lg border border-border-subtle bg-surface-canvas p-3.5">
                                    <div class="flex-1 min-w-0">
                                        <p class="font-semibold text-text-primary">{{ item.product_name }}</p>
                                        <p class="text-xs text-text-tertiary">SKU: {{ item.sku }}</p>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <div class="w-20">
                                            <label class="mb-1 block text-[10px] uppercase text-text-tertiary">{{ t('common.quantity') }}</label>
                                            <input :value="item.quantity" @input="updateItemQuantity(index, parseInt($event.target.value))" type="number" min="1" :class="fieldInput" />
                                        </div>
                                        <div class="w-28">
                                            <label class="mb-1 block text-[10px] uppercase text-text-tertiary">{{ t('common.price') }}</label>
                                            <input :value="item.unit_price" @input="updateItemPrice(index, $event.target.value)" type="number" step="1" min="0" :class="fieldInput" />
                                        </div>
                                        <div class="w-28 text-right">
                                            <label class="mb-1 block text-[10px] uppercase text-text-tertiary">{{ t('common.total') }}</label>
                                            <p class="font-bold tabular-nums text-text-primary text-sm">{{ formatCurrency(item.quantity * item.unit_price) }}</p>
                                        </div>
                                        <button type="button" @click="removeItem(index)" class="mt-4 rounded-md p-1.5 text-text-tertiary transition-colors hover:bg-surface-sunken hover:text-status-danger" title="Futa">
                                            <Trash2 :size="16" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="flex flex-col items-center justify-center py-10 text-center">
                                <PackageOpen :size="32" class="text-text-tertiary mb-2 opacity-60" />
                                <p class="text-sm font-medium text-text-secondary">Kikapu cha mauzo kipo wazi</p>
                                <p class="text-xs text-text-tertiary mt-1">Andika jina au skana barcode juu ili kuongeza bidhaa</p>
                            </div>
                            <p v-if="form.errors.items" :class="fieldError">{{ form.errors.items }}</p>
                        </div>
                    </Card>

                    <!-- Customer Info (Simple / Walk-in Toggle) -->
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

                        <div class="p-5">
                            <!-- Walk-in Banner -->
                            <div v-if="isWalkIn" class="flex items-center gap-3 p-3 rounded-lg bg-surface-canvas border border-border-subtle text-xs text-text-secondary">
                                <UserCheck :size="18" class="text-brand shrink-0" />
                                <div>
                                    <p class="font-medium text-text-primary">Mauzo ya Mteja wa Kawaida (Walk-in Customer)</p>
                                    <p class="text-text-tertiary">Hakuna taarifa za ziada za mteja zinazohitajika. Bonyeza "Mteja Maalum" kama unataka kurekodi jina au kutoa ankara (invoice).</p>
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

                <!-- Right Column: Payment, Summary & Submit -->
                <div class="space-y-5">
                    <!-- Payment Methods (Tanzania friendly) -->
                    <Card :padded="false">
                        <div class="px-5 pt-4 pb-3 border-b border-border-subtle">
                            <h3 class="text-sm font-semibold text-text-primary">{{ t('orders.create.paymentMethod') }}</h3>
                        </div>
                        <div class="p-4 grid grid-cols-2 gap-2.5">
                            <button
                                type="button"
                                @click="setPaymentMethod('cash')"
                                :class="[
                                    'flex flex-col items-center justify-center p-3 rounded-lg border text-xs font-semibold transition-all',
                                    paymentMethod === 'cash'
                                        ? 'border-brand bg-brand/10 text-brand shadow-xs ring-1 ring-brand'
                                        : 'border-border-subtle bg-surface-canvas text-text-secondary hover:border-border-strong'
                                ]"
                            >
                                <Banknote :size="20" class="mb-1" />
                                <span>Pesa Taslimu</span>
                            </button>

                            <button
                                type="button"
                                @click="setPaymentMethod('mpesa')"
                                :class="[
                                    'flex flex-col items-center justify-center p-3 rounded-lg border text-xs font-semibold transition-all',
                                    paymentMethod === 'mpesa'
                                        ? 'border-status-success bg-status-success/10 text-status-success shadow-xs ring-1 ring-status-success'
                                        : 'border-border-subtle bg-surface-canvas text-text-secondary hover:border-border-strong'
                                ]"
                            >
                                <Smartphone :size="20" class="mb-1" />
                                <span>M-Pesa / Tigo / Airtel</span>
                            </button>

                            <button
                                type="button"
                                @click="setPaymentMethod('bank')"
                                :class="[
                                    'flex flex-col items-center justify-center p-3 rounded-lg border text-xs font-semibold transition-all',
                                    paymentMethod === 'bank'
                                        ? 'border-brand bg-brand/10 text-brand shadow-xs ring-1 ring-brand'
                                        : 'border-border-subtle bg-surface-canvas text-text-secondary hover:border-border-strong'
                                ]"
                            >
                                <CreditCard :size="20" class="mb-1" />
                                <span>Benki / Kadi</span>
                            </button>

                            <button
                                type="button"
                                @click="setPaymentMethod('credit')"
                                :class="[
                                    'flex flex-col items-center justify-center p-3 rounded-lg border text-xs font-semibold transition-all',
                                    paymentMethod === 'credit'
                                        ? 'border-status-danger bg-status-danger/10 text-status-danger shadow-xs ring-1 ring-status-danger'
                                        : 'border-border-subtle bg-surface-canvas text-text-secondary hover:border-border-strong'
                                ]"
                            >
                                <FileText :size="20" class="mb-1" />
                                <span>Mkopo / Deni</span>
                            </button>
                        </div>
                    </Card>

                    <!-- Order Details & Notes -->
                    <Card :padded="false">
                        <div class="px-5 pt-4 pb-3 border-b border-border-subtle">
                            <h3 class="text-sm font-semibold text-text-primary">{{ t('orders.create.orderDetails') }}</h3>
                        </div>
                        <div class="space-y-3 p-4">
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
                            <div>
                                <label for="notes" :class="fieldLabel">{{ t('common.notes') }}</label>
                                <textarea id="notes" v-model="form.notes" rows="2" :class="fieldArea" placeholder="Vidokezo vya mauzo..."></textarea>
                            </div>
                        </div>
                    </Card>

                    <!-- Order Summary Card -->
                    <Card :padded="false">
                        <div class="px-5 pt-4 pb-3 border-b border-border-subtle">
                            <h3 class="text-sm font-semibold text-text-primary">{{ t('orders.create.orderSummary') }}</h3>
                        </div>
                        <div class="space-y-3 p-5">
                            <div class="flex justify-between text-sm">
                                <span class="text-text-secondary">{{ t('common.subtotal') }}</span>
                                <span class="font-semibold tabular-nums text-text-primary">{{ formatCurrency(subtotal) }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-4 text-xs">
                                <span class="text-text-secondary">{{ t('common.tax') }}</span>
                                <input v-model.number="form.tax" type="number" step="1" min="0" placeholder="0" class="h-8 w-28 rounded border border-border-subtle bg-surface-canvas px-2 text-right text-xs" />
                            </div>
                            <div class="flex items-center justify-between gap-4 text-xs">
                                <span class="text-text-secondary">{{ t('common.shipping') }}</span>
                                <input v-model.number="form.shipping" type="number" step="1" min="0" placeholder="0" class="h-8 w-28 rounded border border-border-subtle bg-surface-canvas px-2 text-right text-xs" />
                            </div>
                            <div class="flex items-center justify-between border-t border-border-subtle pt-3">
                                <span class="text-base font-bold text-text-primary">{{ t('common.total') }}</span>
                                <span class="text-2xl font-extrabold text-brand tabular-nums">{{ formatCurrency(total) }}</span>
                            </div>
                        </div>
                    </Card>

                    <!-- Actions -->
                    <div class="flex flex-col gap-2">
                        <Button type="submit" variant="default" size="lg" class="w-full text-base font-bold py-3 shadow-md" :loading="form.processing" :disabled="form.processing || form.items.length === 0">
                            <CheckCircle2 :size="18" />
                            {{ form.processing ? t('common.saving') : t('orders.create.createOrder') }}
                        </Button>
                        <Button variant="secondary" size="md" class="w-full" as="Link" :href="route('orders.index')">{{ t('common.cancel') }}</Button>
                    </div>
                </div>
            </div>
        </form>
    </AppLayout>
</template>
