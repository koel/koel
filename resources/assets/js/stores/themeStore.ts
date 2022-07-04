import { reactive } from 'vue'
import { preferenceStore as preferences } from '@/stores'

import bgRosePetal from '@/../img/themes/bg-rose-petals.svg'
import bgPurpleWaves from '@/../img/themes/bg-purple-waves.svg'
import bgPopCulture from '@/../img/themes/bg-pop-culture.jpg'
import bgJungle from '@/../img/themes/bg-jungle.jpg'
import bgMountains from '@/../img/themes/bg-mountains.jpg'
import bgPines from '@/../img/themes/bg-pines.jpg'
import bgNemo from '@/../img/themes/bg-nemo.jpg'
import bgCat from '@/../img/themes/bg-cat.jpg'
import { clone } from 'lodash'

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
  } as Record<ThemeableProperty, string>,

  state: reactive({
    themes: [
      {
        id: 'classic',
        thumbnailColor: '#181818'
      },
      {
        id: 'violet',
        thumbnailColor: '#31094e',
        properties: {
          '--color-bg-primary': '#31094e'
        }
      },
      {
        id: 'oak',
        thumbnailColor: '#560d25',
        properties: {
          '--color-bg-primary': '#560d25'
        }
      },
      {
        id: 'slate',
        thumbnailColor: '#29434e',
        properties: {
          '--color-bg-primary': '#29434e'
        }
      },
      {
        id: 'madison',
        thumbnailColor: '#0e3463',
        properties: {
          '--color-bg-primary': '#0e3463',
          '--color-bg-highlight': '#fbab18'
        }
      },
      {
        id: 'astronaut',
        thumbnailColor: '#2a3074',
        properties: {
          '--color-bg-primary': '#2a3074'
        }
      },
      {
        id: 'chocolate',
        thumbnailColor: '#3f2724',
        properties: {
          '--color-bg-primary': '#3f2724'
        }
      },
      {
        id: 'laura',
        thumbnailColor: '#126673',
        properties: {
          '--color-bg-primary': '#126673',
          '--color-highlight': 'rgba(10, 244, 255, .64)'
        }
      },
      {
        id: 'rose-petals',
        name: '…Has Its Thorns',
        thumbnailColor: '#7d083b',
        thumbnailUrl: bgRosePetal,
        properties: {
          '--color-bg-primary': '#7d083b',
          '--bg-image': `url(${bgRosePetal})`
        }
      },
      {
        id: 'purple-waves',
        name: 'Waves of Fortune',
        thumbnailColor: '#44115c',
        thumbnailUrl: bgPurpleWaves,
        properties: {
          '--color-bg-primary': '#44115c',
          '--bg-image': `url(${bgPurpleWaves})`
        }
      },
      {
        id: 'pop-culture',
        thumbnailColor: '#ad0937',
        thumbnailUrl: bgPopCulture,
        properties: {
          '--color-bg-primary': '#ad0937',
          '--color-highlight': 'rgba(234, 208, 110, .9)',
          '--bg-image': `url(${bgPopCulture})`
        }
      },
      {
        id: 'jungle',
        name: 'Welcome to the Jungle',
        thumbnailColor: '#0f0f03',
        thumbnailUrl: bgJungle,
        properties: {
          '--color-bg-primary': '#0f0f03',
          '--bg-image': `url(${bgJungle})`
        }
      },
      {
        id: 'mountains',
        name: 'Rocky Mountain High',
        thumbnailColor: '#0e2656',
        thumbnailUrl: bgMountains,
        properties: {
          '--color-bg-primary': '#0e2656',
          '--bg-image': `url(${bgMountains})`
        }
      },
      {
        id: 'pines',
        name: 'In the Pines',
        thumbnailColor: '#06090c',
        thumbnailUrl: bgPines,
        properties: {
          '--color-bg-primary': '#06090c',
          '--color-highlight': '#5984b9',
          '--bg-image': `url(${bgPines})`
        }
      },
      {
        id: 'nemo',
        thumbnailColor: '#031724',
        thumbnailUrl: bgNemo,
        properties: {
          '--color-bg-primary': '#031724',
          '--bg-image': `url(${bgNemo})`
        }
      },
      {
        id: 'cat',
        name: 'What\'s New Pussycat?',
        thumbnailColor: '#000',
        thumbnailUrl: bgCat,
        properties: {
          '--color-bg-primary': '#000',
          '--bg-image': `url(${bgCat})`,
          '--bg-position': 'left'
        }
      }
    ] as Theme[]
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
