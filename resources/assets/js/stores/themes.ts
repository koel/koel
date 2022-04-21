import { reactive } from 'vue'
import { preferenceStore as preferences } from '@/stores/preference'

import bgRosePetal from '@/../img/themes/bg-rose-petals.svg'
import bgPurpleWaves from '@/../img/themes/bg-purple-waves.svg'
import bgPopCulture from '@/../img/themes/bg-pop-culture.jpg'
import bgJungle from '@/../img/themes/bg-jungle.jpg'
import bgMountains from '@/../img/themes/bg-mountains.jpg'
import bgPines from '@/../img/themes/bg-pines.jpg'
import bgNemo from '@/../img/themes/bg-nemo.jpg'
import bgCat from '@/../img/themes/bg-cat.jpg'

export const themeStore = {
  state: reactive({
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
        thumbnailUrl: bgRosePetal
      },
      {
        id: 'purple-waves',
        name: 'Waves of Fortune',
        thumbnailColor: '#44115c',
        thumbnailUrl: bgPurpleWaves
      },
      {
        id: 'pop-culture',
        thumbnailColor: '#ad0937',
        thumbnailUrl: bgPopCulture
      },
      {
        id: 'jungle',
        name: 'Welcome to the Jungle',
        thumbnailColor: '#0f0f03',
        thumbnailUrl: bgJungle
      },
      {
        id: 'mountains',
        name: 'Rocky Mountain High',
        thumbnailColor: '#0e2656',
        thumbnailUrl: bgMountains
      },
      {
        id: 'pines',
        name: 'In the Pines',
        thumbnailColor: '#06090c',
        thumbnailUrl: bgPines
      },
      {
        id: 'nemo',
        thumbnailColor: '#031724',
        thumbnailUrl: bgNemo
      },
      {
        id: 'cat',
        name: 'What\'s New Pussycat?',
        thumbnailColor: '#000',
        thumbnailUrl: bgCat
      }
    ] as Theme[]
  }),

  init () {
    this.applyThemeFromPreference()
  },

  setTheme (theme: Theme) {
    document.documentElement.setAttribute('data-theme', theme.id)
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
