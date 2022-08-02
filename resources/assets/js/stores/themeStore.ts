import { clone } from 'lodash'
import { reactive } from 'vue'
import { preferenceStore as preferences } from '@/stores'
import themes from '@/themes'

export const themeStore = {
  defaultProperties: {
    '--color-text-primary': undefined,
    '--color-text-secondary': undefined,
    '--color-bg-primary': undefined,
    '--color-bg-secondary': undefined,
    '--color-highlight': undefined,
    '--bg-image': undefined,
    '--bg-position': undefined,
    '--bg-attachment': undefined,
    '--bg-size': undefined
  } as Record<ThemeableProperty, string | undefined>,

  state: reactive({
    themes
  }),

  init () {
    for (let key in this.defaultProperties) {
      this.defaultProperties[key] = document.documentElement.style.getPropertyValue(key)
    }

    this.applyThemeFromPreference()
  },

  setTheme (theme: Theme) {
    document.documentElement.setAttribute('data-theme', theme.id)
    let properties = Object.assign(clone(this.defaultProperties), theme.properties ?? {})

    for (let key in properties) {
      document.documentElement.style.setProperty(key, properties[key])
    }

    preferences.theme = theme.id
    this.state.themes.forEach(t => (t.selected = t.id === theme.id))
  },

  getThemeById (id: string) {
    return this.state.themes.find(theme => theme.id === id)
  },

  getDefaultTheme (): Theme {
    return this.getThemeById('classic')!
  },

  applyThemeFromPreference (): void {
    const theme = preferences.theme
      ? (this.getThemeById(preferences.theme) ?? this.getDefaultTheme())
      : this.getDefaultTheme()

    this.setTheme(theme)
  }
}
