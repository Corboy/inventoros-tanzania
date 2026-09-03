import { createI18n } from 'vue-i18n'

import sw from './locales/sw.json'
import en from './locales/en.json'
import es from './locales/es.json'
import fr from './locales/fr.json'
import de from './locales/de.json'
import ptBR from './locales/pt-BR.json'
import it from './locales/it.json'
import ja from './locales/ja.json'
import ko from './locales/ko.json'
import zhCN from './locales/zh-CN.json'
import ar from './locales/ar.json'
import ru from './locales/ru.json'
import nl from './locales/nl.json'
import tr from './locales/tr.json'
import pl from './locales/pl.json'

const savedLocale = localStorage.getItem('locale')

const i18n = createI18n({
    legacy: false,
    locale: savedLocale || document.documentElement.lang || 'sw',
    fallbackLocale: 'en',
    messages: {
        sw,
        en,
        es,
        fr,
        de,
        'pt-BR': ptBR,
        it,
        ja,
        ko,
        'zh-CN': zhCN,
        ar,
        ru,
        nl,
        tr,
        pl,
    },
})

export const availableLocales = [
    { code: 'sw', name: 'Kiswahili', flag: '🇹🇿' },
    { code: 'en', name: 'English', flag: '🇬🇧' },
    { code: 'es', name: 'Español', flag: '🇪🇸' },
    { code: 'fr', name: 'Français', flag: '🇫🇷' },
    { code: 'de', name: 'Deutsch', flag: '🇩🇪' },
    { code: 'pt-BR', name: 'Português (BR)', flag: '🇧🇷' },
    { code: 'it', name: 'Italiano', flag: '🇮🇹' },
    { code: 'ja', name: '日本語', flag: '🇯🇵' },
    { code: 'ko', name: '한국어', flag: '🇰🇷' },
    { code: 'zh-CN', name: '中文', flag: '🇨🇳' },
    { code: 'ar', name: 'العربية', flag: '🇸🇦' },
    { code: 'ru', name: 'Русский', flag: '🇷🇺' },
    { code: 'nl', name: 'Nederlands', flag: '🇳🇱' },
    { code: 'tr', name: 'Türkçe', flag: '🇹🇷' },
    { code: 'pl', name: 'Polski', flag: '🇵🇱' },
]

export function setLocale(locale) {
    i18n.global.locale.value = locale
    localStorage.setItem('locale', locale)
    document.documentElement.lang = locale
    document.cookie = `locale=${locale};path=/;max-age=${60 * 60 * 24 * 365};SameSite=Lax`
}

export default i18n
