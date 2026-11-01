<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import Card from '@/Components/ui/Card.vue';
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import {
    Building2,
    UserCircle,
    Mail,
    Webhook,
    ChevronRight,
    RotateCcw,
    Activity,
    Puzzle,
} from 'lucide-vue-next';

const { t } = useI18n();
const page = usePage();
const user = computed(() => page.props.auth?.user);
const isManager = computed(() => user.value?.role === 'manager');

const allSections = [
    {
        href: route('settings.organization.index'),
        icon: Building2,
        title: t('settings.organization.title'),
        description: "Manage your organization's profile, regional preferences, and team members.",
    },
    {
        href: route('settings.account.index'),
        icon: UserCircle,
        title: t('settings.account.title'),
        description: 'Update your profile, password, notifications, and personal preferences.',
    },
    {
        href: route('settings.email.index'),
        icon: Mail,
        title: t('settings.email.title'),
        description: 'Configure how your organization sends email notifications.',
    },
    {
        href: route('webhooks.index'),
        icon: Webhook,
        title: 'Webhooks (Dev)',
        description: 'Connect external services and deliver real-time event notifications.',
        devOnly: true,
    },
    {
        href: route('admin.update.index'),
        icon: RotateCcw,
        title: 'System Updates & Backups (Dev)',
        description: 'Check for software updates, perform backups and system restorations.',
        devOnly: true,
    },
    {
        href: route('activity-log.index'),
        icon: Activity,
        title: 'Activity & Audit Log (Dev)',
        description: 'Review system events, logins, stock movements and user actions.',
        devOnly: true,
    },
    {
        href: route('plugins.index'),
        icon: Puzzle,
        title: 'Plugins & Extensions (Dev)',
        description: 'Manage developer modules, extensions, and integration hooks.',
        devOnly: true,
    },
];

const settingsSections = computed(() =>
    allSections.filter((section) => {
        if (section.devOnly && isManager.value) {
            return false;
        }
        return true;
    })
);
</script>

<template>
    <Head :title="t('settings.title')" />

    <AppLayout>
        <template #header>
            <div class="flex items-center gap-2 text-xs">
                <span class="text-text-tertiary">Workspace</span>
                <span class="text-text-tertiary">/</span>
                <span class="font-medium text-text-primary">{{ t('settings.title') }}</span>
            </div>
        </template>

        <PageHeader
            :title="t('settings.title')"
            description="Manage your workspace, account, and integrations."
        />

        <!-- Settings sections -->
        <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            <Link
                v-for="section in settingsSections"
                :key="section.href"
                :href="section.href"
            >
                <Card hoverable>
                    <div class="flex items-start gap-4">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-soft text-brand">
                            <component :is="section.icon" :size="18" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <h3 class="text-sm font-semibold text-text-primary">{{ section.title }}</h3>
                            <p class="mt-1 text-sm text-text-secondary">{{ section.description }}</p>
                        </div>
                        <ChevronRight :size="16" class="mt-0.5 shrink-0 text-text-tertiary" />
                    </div>
                </Card>
            </Link>
        </div>
    </AppLayout>
</template>
