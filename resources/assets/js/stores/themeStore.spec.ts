import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { preferenceStore } from '@/stores/preferenceStore'
import { themeStore } from '@/stores/themeStore'

const testTheme: Theme = {
  id: 'test',
  name: 'Test Theme',
  thumbnailColor: '#eee',
  properties: {
    '--color-text-primary': '#eee',
    '--color-text-secondary': '#ddd',
    '--bg-image': '/images/bg.jpg',
  },
}

describe('themeStore', () => {
  const h = createHarness({
    beforeEach: () => {
      document.documentElement.style.setProperty('--color-text-primary', '#fff')
      document.documentElement.style.setProperty('--color-text-secondary', '#ccc')
      document.documentElement.style.setProperty('--color-bg-primary', '#000')
      document.documentElement.style.setProperty('--color-highlight', 'orange')
    },
    afterEach: () => {
      for (const key in themeStore.defaultProperties) {
        document.documentElement.style.removeProperty(key)
      }

      document.documentElement.removeAttribute('data-theme')
      delete (preferenceStore as any).theme
    },
  })

  it('initializes the store', () => {
    const setThemeMock = h.mock(themeStore, 'setTheme')

    themeStore.init()

    expect(themeStore.defaultProperties).toEqual({
      '--color-text-primary': '#fff',
      '--color-text-secondary': '#ccc',
      '--color-bg-primary': '#000',
      '--color-bg-secondary': '',
      '--color-highlight': 'orange',
      '--bg-image': '',
      '--bg-position': '',
      '--bg-attachment': '',
      '--bg-size': '',
      '--font-family': '',
      '--font-size': '',
    })

    expect(setThemeMock).toHaveBeenCalledWith('classic')
  })

  it('sets a theme', () => {
    themeStore.setTheme(testTheme)

    expect(document.documentElement.getAttribute('data-theme')).toEqual('test')
    expect(document.documentElement.style.getPropertyValue('--color-text-primary')).toEqual('#eee')
    expect(document.documentElement.style.getPropertyValue('--color-text-secondary')).toEqual('#ddd')
    expect(document.documentElement.style.getPropertyValue('--bg-image')).toEqual('/images/bg.jpg')

    themeStore.setTheme({
      id: 'another',
      name: 'Another Theme',
      thumbnailColor: '#ccc',
      properties: {
        '--color-text-primary': '#ccc',
      },
    })

    expect(document.documentElement.getAttribute('data-theme')).toEqual('another')
    // verify that non-existent theme properties are reset back to the default
    expect(document.documentElement.style.getPropertyValue('--color-text-primary')).toEqual('#ccc')
    expect(document.documentElement.style.getPropertyValue('--color-text-secondary')).toEqual('#ccc')
    expect(document.documentElement.style.getPropertyValue('--bg-image')).toEqual('')
  })

  it('gets a theme by id', () => {
    themeStore.state.themes.push(testTheme)
    expect(themeStore.getThemeById('test')).toEqual(testTheme)
  })

  it('gets the default theme', () => {
    expect(themeStore.getDefaultTheme().id).toEqual('classic')
  })
})
