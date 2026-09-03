<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { availableLocales, setLocale } from '@/i18n'

const { locale } = useI18n()
const isOpen = ref(false)
const dropdownRef = ref(null)

const currentLocale = () => {
    return availableLocales.find(l => l.code === locale.value) || availableLocales[0]
}

const switchLocale = (code) => {
    setLocale(code)
    isOpen.value = false
}

const handleClickOutside = (event) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        isOpen.value = false
    }
}

onMounted(() => document.addEventListener('click', handleClickOutside))
onUnmounted(() => document.removeEventListener('click', handleClickOutside))
</script>

<template>
    <div ref="dropdownRef" class="relative">
        <button
            @click="isOpen = !isOpen"
            type="button"
            class="flex items-center gap-1.5 h-8 px-2.5 rounded-md text-xs font-medium text-text-secondary hover:text-text-primary hover:bg-surface-overlay transition-colors ds-focus-ring"
            title="Change Language / Badili Lugha"
        >
            <span class="text-sm leading-none">{{ currentLocale().flag }}</span>
            <span class="hidden sm:inline text-xs">{{ currentLocale().name }}</span>
            <svg class="w-3 h-3 text-text-tertiary transition-transform duration-200" :class="{ 'rotate-180': isOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <Transition
            enter-active-class="transition ease-out duration-150"
            enter-from-class="opacity-0 translate-y-1 scale-95"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100 translate-y-0 scale-100"
            leave-to-class="opacity-0 translate-y-1 scale-95"
        >
            <div
                v-if="isOpen"
                class="absolute right-0 top-full mt-1.5 w-44 bg-surface-base border border-border-subtle rounded-lg shadow-xl overflow-hidden z-50 max-h-80 overflow-y-auto py-1"
            >
                <button
                    v-for="loc in availableLocales"
                    :key="loc.code"
                    type="button"
                    @click="switchLocale(loc.code)"
                    :class="[
                        'w-full flex items-center gap-2.5 px-3 py-2 text-xs font-medium transition-colors text-left',
                        locale === loc.code
                            ? 'bg-brand/10 text-brand font-semibold'
                            : 'text-text-secondary hover:text-text-primary hover:bg-surface-overlay'
                    ]"
                >
                    <span class="text-sm leading-none">{{ loc.flag }}</span>
                    <span class="flex-1 truncate">{{ loc.name }}</span>
                    <span v-if="locale === loc.code" class="text-brand text-xs">✓</span>
                </button>
            </div>
        </Transition>
    </div>
</template>
