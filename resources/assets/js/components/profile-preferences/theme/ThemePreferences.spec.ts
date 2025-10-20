import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { screen } from '@testing-library/vue'
import themes from '@/themes'
import { themeStore } from '@/stores/themeStore'
import { eventBus } from '@/utils/eventBus'
import Component from './ThemePreferences.vue'

describe('themeList.vue', () => {
  const h = createHarness()

  it('renders the list of built-in themes', () => {
    h.render(Component)

    screen.getByTestId('built-in-themes')
    expect(screen.queryAllByTestId('theme-card')).toHaveLength(themes.length)
    expect(screen.queryByTestId('custom-themes')).toBeNull()
  })

  it('renders custom themes and other elements for Plus edition', async () => {
    await h.withPlusEdition(async () => {
      const emitMock = h.mock(eventBus, 'emit')

      h.mock(themeStore, 'fetchCustomThemes')
        .mockImplementation(() => {
          // manually modify the internal state, as the mock will prevent the real method from running
          themeStore.state.themes = themeStore.state.themes.concat(...h.factory('theme', 4))
        })

      h.render(Component)
      await h.tick()

      screen.getByTestId('built-in-themes')
      screen.getByTestId('custom-themes')

      expect(screen.queryAllByTestId('theme-card')).toHaveLength(themes.length + 4)

      // there's a button to open the "new theme" form
      await h.user.click(screen.getByRole('button', { name: 'New Theme' }))
      expect(emitMock).toHaveBeenCalledWith('MODAL_SHOW_CREATE_THEME_FORM')
    })
  })
})
