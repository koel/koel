import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import themes from '@/themes'
import { themeStore } from '@/stores/themeStore'
import Component from './ThemeList.vue'

describe('themeList.vue', () => {
  const h = createHarness()

  it('displays all themes', () => {
    themeStore.init()
    expect(h.render(Component).getAllByTestId('theme-card').length).toEqual(themes.length)
  })
})
