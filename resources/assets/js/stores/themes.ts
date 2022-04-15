import { preferenceStore as preferences } from '@/stores/preference'

export const themeStore = {
  state: {
    themes: [
      {
        id: 'classic',
        thumbnailColor: '#181818'
      },
      {
        id: 'violet',
        thumbnailColor: '#31094e'
      },
      {
        id: 'oak',
        thumbnailColor: '#560d25'
      },
      {
        id: 'slate',
        thumbnailColor: '#29434e'
      },
      {
        id: 'madison',
        thumbnailColor: '#0e3463'
      },
      {
        id: 'astronaut',
        thumbnailColor: '#2a3074'
      },
      {
        id: 'chocolate',
        thumbnailColor: '#3f2724'
      },
      {
        id: 'laura',
        thumbnailColor: '#126673'
      },
      {
        id: 'rose-petals',
        name: '…Has Its Thorns',
        thumbnailColor: '#7d083b',
        thumbnailUrl: require('@/../img/themes/bg-rose-petals.svg')
      },
      {
        id: 'purple-waves',
        name: 'Waves of Fortune',
        thumbnailColor: '#44115c',
        thumbnailUrl: require('@/../img/themes/bg-purple-waves.svg')
      },
      {
        id: 'pop-culture',
        thumbnailColor: '#ad0937',
        thumbnailUrl: require('@/../img/themes/bg-pop-culture.jpg')
      },
      {
        id: 'jungle',
        name: 'Welcome to the Jungle',
        thumbnailColor: '#0f0f03',
        thumbnailUrl: require('@/../img/themes/bg-jungle.jpg')
      },
      {
        id: 'mountains',
        name: 'Rocky Mountain High',
        thumbnailColor: '#0e2656',
        thumbnailUrl: require('@/../img/themes/bg-mountains.jpg')
      },
      {
        id: 'pines',
        name: 'In the Pines',
        thumbnailColor: '#06090c',
        thumbnailUrl: require('@/../img/themes/bg-pines.jpg')
      },
      {
        id: 'nemo',
        thumbnailColor: '#031724',
        thumbnailUrl: require('@/../img/themes/bg-nemo.jpg')
      },
      {
        id: 'cat',
        name: 'What\'s New Pussycat?',
        thumbnailColor: '#000',
        thumbnailUrl: require('@/../img/themes/bg-cat.jpg')
      }
    ] as Theme[]
  },

  init () {
    this.applyThemeFromPreference()
  },

  setTheme (theme: Theme) {
    document.documentElement.setAttribute('data-theme', theme.id)
    preferences.theme = theme.id

    this.state.themes.forEach(t => {
      t.selected = t.id === theme.id
    })
  },

  getThemeById (id: string): Theme | undefined {
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
