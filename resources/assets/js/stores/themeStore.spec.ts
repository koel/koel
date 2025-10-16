import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { preferenceStore } from '@/stores/preferenceStore'
import type { ThemeData } from '@/stores/themeStore'
import { themeStore } from '@/stores/themeStore'
import { http } from '@/services/http'
import { cache } from '@/services/cache'
import builtInThemes from '@/themes'

describe('themeStore', () => {
  const h = createHarness({
    beforeEach: () => {
      document.body.style.setProperty('--color-fg', '#ffffff')
      document.body.style.setProperty('--color-bg', '#000000')
      document.body.style.setProperty('--color-highlight', '#ff0000')
      document.body.style.setProperty('--font-family', 'system-ui')
      document.body.style.setProperty('--font-size', '16px')
    },
    afterEach: () => {
      for (const key in themeStore.defaultProperties) {
        document.body.style.removeProperty(key)
      }

      document.documentElement.removeAttribute('data-theme')
      delete (preferenceStore as any).theme
    },
  })

  it('initializes the store', () => {
    const setThemeMock = h.mock(themeStore, 'setTheme')

    themeStore.init()

    expect(themeStore.defaultProperties).toEqual({
      '--color-fg': '#ffffff',
      '--color-bg': '#000000',
      '--color-highlight': '#ff0000',
      '--bg-image': '',
      '--bg-position': '',
      '--bg-attachment': '',
      '--bg-size': '',
      '--font-family': 'system-ui',
      '--font-size': '16px',
    })

    expect(setThemeMock).toHaveBeenCalledWith('classic')
  })

  it('initializes the store with a custom theme', () => {
    const setThemeMock = h.mock(themeStore, 'setTheme')

    const theme = h.factory('theme', {
      properties: {
        '--color-fg': '#00ff00',
        '--color-bg': '#111111',
        '--color-highlight': '#0000ff',
        '--bg-image': 'url("/images/bg.jpg")',
        '--font-family': 'Comic Sans MS, cursive, sans-serif',
        '--font-size': '14.5px',
      },
    })

    themeStore.init(theme)

    expect(themeStore.all.filter(({ id }) => id === theme.id)).toBeTruthy()
    expect(setThemeMock).toHaveBeenCalledWith(theme)
  })

  it('sets a theme', () => {
    const theme = h.factory('theme', {
      properties: {
        '--color-fg': '#ffffff',
        '--color-bg': '#000000',
        '--color-highlight': '#ff0000',
        '--bg-image': 'url("/images/bg.jpg"',
        '--font-size': '16px',
      },
    })

    themeStore.setTheme(theme)

    expect(document.documentElement.getAttribute('data-theme')).toEqual(theme.id)

    for (const key in theme.properties) {
      expect(document.body.style.getPropertyValue(key)).toEqual(theme.properties[key])
    }
  })

  it('gets a theme by id', () => {
    const theme = h.factory('theme')
    themeStore.all.push(theme)
    expect(themeStore.getThemeById(theme.id)).toEqual(theme)
  })

  it('gets the default theme', () => {
    expect(themeStore.getDefaultTheme().id).toEqual('classic')
  })

  it('creates a custom theme', async () => {
    const createdTheme = h.factory('theme')
    const postMock = h.mock(http, 'post').mockResolvedValueOnce(createdTheme)

    const data: ThemeData = {
      name: 'One Theme to Rule Them All',
      fg_color: '#ffffff',
      bg_color: '#000000',
      highlight_color: '#ff0000',
      bg_image: 'none',
      font_family: 'system-ui',
      font_size: 16.5,
    }

    await themeStore.store(data)

    expect(postMock).toHaveBeenCalledWith('themes', data)
    expect(themeStore.all[0]).toEqual(createdTheme)
  })

  it('fetches custom themes', async () => {
    const customThemes = h.factory('theme', 5)
    const getMock = h.mock(http, 'get').mockResolvedValueOnce(customThemes)

    await themeStore.fetchCustomThemes()

    expect(getMock).toHaveBeenCalled()
    expect(cache.get('custom-themes')).toBe(customThemes)
    expect(themeStore.all).toHaveLength(builtInThemes.length + 5)
    customThemes.forEach(({ id }) => expect(themeStore.getThemeById(id)).toBeTruthy())
  })

  it('deletes a custom theme', async () => {
    const customTheme = h.factory('theme')
    themeStore.all.push(customTheme)
    const deleteMock = h.mock(http, 'delete')
    await themeStore.destroy(customTheme)

    expect(deleteMock)
  })
})
