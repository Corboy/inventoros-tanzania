<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const fillCredentials = (email, password = 'password123') => {
    form.email = email;
    form.password = password;
};

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head :title="t('auth.login.title')" />

        <div v-if="status" class="mb-4 text-sm font-medium text-status-success">
            {{ status }}
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="email" :value="t('auth.login.email')" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" :value="t('auth.login.password')" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4 block">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-sm text-text-secondary"
                        >{{ t('auth.login.rememberMe') }}</span
                    >
                </label>
            </div>

            <div class="mt-4 flex items-center justify-between">
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="rounded-md text-xs text-text-secondary underline hover:text-text-primary ds-focus-ring"
                >
                    {{ t('auth.login.forgotPassword') }}
                </Link>
                <div v-else></div>

                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    {{ t('auth.login.submit') }}
                </PrimaryButton>
            </div>
        </form>

        <!-- Quick Account Selector (Akaunti za Kuingilia Haraka) -->
        <div class="mt-6 pt-5 border-t border-border-subtle">
            <p class="text-xs font-semibold uppercase tracking-wider text-text-tertiary mb-3 text-center">
                Au Chagua Akaunti ya Kuingilia Haraka:
            </p>
            <div class="grid grid-cols-3 gap-2">
                <button
                    type="button"
                    @click="fillCredentials('admin@biashara.co.tz')"
                    class="py-2 px-2 text-center rounded-lg border border-border-subtle bg-surface-canvas hover:border-brand hover:bg-brand-soft text-xs transition-colors"
                >
                    <div class="font-bold text-text-primary">Admin</div>
                    <div class="text-[10px] text-text-tertiary">Mwenye Duka</div>
                </button>
                <button
                    type="button"
                    @click="fillCredentials('meneja@biashara.co.tz')"
                    class="py-2 px-2 text-center rounded-lg border border-border-subtle bg-surface-canvas hover:border-brand hover:bg-brand-soft text-xs transition-colors"
                >
                    <div class="font-bold text-text-primary">Meneja</div>
                    <div class="text-[10px] text-text-tertiary">Manager</div>
                </button>
                <button
                    type="button"
                    @click="fillCredentials('muuzaji@biashara.co.tz')"
                    class="py-2 px-2 text-center rounded-lg border border-border-subtle bg-surface-canvas hover:border-brand hover:bg-brand-soft text-xs transition-colors"
                >
                    <div class="font-bold text-text-primary">Cashier</div>
                    <div class="text-[10px] text-text-tertiary">Muuzaji</div>
                </button>
            </div>
            <p class="mt-2 text-center text-[11px] text-text-tertiary">
                Nenosiri (Password): <code class="font-mono text-brand font-semibold">password123</code>
            </p>
        </div>
    </GuestLayout>
</template>
