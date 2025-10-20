import { clone, uniqBy } from 'lodash'
import Color from 'color'
import StyleObserver from 'style-observer'
import { reactive } from 'vue'
import { preferenceStore as preferences } from '@/stores/preferenceStore'
import { http } from '@/services/http'
import themes from '@/themes'
import { cache } from '@/services/cache'

export interface ThemeData {
  name: string
  font_family: string
  font_size: number
  bg_color: string
  fg_color: string
  highlight_color: string
  bg_image: string
}

export const themeStore = {
  defaultProperties: {
    '--color-fg': undefined,
    '--color-bg': undefined,
    '--color-highlight': undefined,
    '--bg-image': undefined,
    '--bg-position': undefined,
    '--bg-attachment': undefined,
    '--bg-size': undefined,
    '--font-family': undefined,
    '--font-size': undefined,
  } as Record<ThemeableProperty, string | undefined>,

  state: reactive({
    themes,
  }),

  init (theme: Theme | Theme['id'] = 'classic') {
    for (const key in this.defaultProperties) {
      this.defaultProperties[key] = document.body.style.getPropertyValue(key)
    }

    // calculate and set the highlight foreground color
    const observer = new StyleObserver(([{ value }]) => {
      document.body.style.setProperty(
        '--color-highlight-fg',
        Color(value).isDark() ? '#ffffff' : '#111111',
      )
    })

    observer.observe(document.body, '--color-highlight')

    if (typeof theme === 'object' && theme.is_custom) {
      // custom theme from server. Add it to the list of themes.
      this.state.themes.push(theme)
    }

    this.setTheme(theme)
  },

  get all () {
    return this.state.themes
  },

  setTheme (theme?: Theme | Theme['id']) {
    if (theme === undefined) {
      return this.setTheme(this.getCurrentTheme())
    }

    if (typeof theme === 'string') {
      theme = this.getThemeById(theme) ?? this.getDefaultTheme()
    }

    document.documentElement.setAttribute('data-theme', theme.id)
    const properties = Object.assign(clone(this.defaultProperties), theme.properties ?? {})

    for (const key in properties) {
      document.body.style.setProperty(key, properties[key]) // overriding :root
    }

    preferences.theme = theme.id
  },

  isCurrentTheme (theme: Theme | Theme['id']) {
    const currentTheme = this.getCurrentTheme()
    return typeof theme === 'string' ? currentTheme.id === theme : currentTheme.id === theme.id
  },

  getThemeById (id: Theme['id']) {
    return this.state.themes.find(theme => theme.id === id)
  },

  getDefaultTheme () {
    return this.getThemeById('classic')!
  },

  getCurrentTheme () {
    return preferences.theme
      ? (this.getThemeById(preferences.theme) ?? this.getDefaultTheme())
      : this.getDefaultTheme()
  },

  isValidTheme (id: Theme['id']) {
    return this.getThemeById(id) !== undefined
  },

  async store (data: ThemeData) {
    const theme = await http.post<Theme>('themes', data)
    this.state.themes.unshift(theme)
    cache.remove('custom-themes')

    return theme
  },

  async fetchCustomThemes () {
    const customThemes = await cache.remember('custom-themes', async () => await http.get<Theme[]>('themes'))
    this.state.themes = uniqBy(this.state.themes.concat(customThemes), 'id')
  },

  async destroy (theme: Theme) {
    if (!theme.is_custom) {
      return
    }

    const isCurrentTheme = this.isCurrentTheme(theme)

    await http.delete(`themes/${theme.id}`)
    this.state.themes = this.state.themes.filter(({ id }) => id !== theme.id)
    cache.remove('custom-themes')

    if (isCurrentTheme) {
      this.setTheme(this.getDefaultTheme())
    }
  },
}
