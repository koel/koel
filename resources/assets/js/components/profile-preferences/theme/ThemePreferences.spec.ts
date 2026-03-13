import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { assertOpenModal } from '@/__tests__/assertions'
import { screen } from '@testing-library/vue'
import themes from '@/config/themes'
import { themeStore } from '@/stores/themeStore'
import CreateThemeForm from '@/components/profile-preferences/theme/CreateThemeForm.vue'

const openModalMock = vi.fn()

vi.mock('@/composables/useModal', () => ({
  useModal: () => ({
    openModal: openModalMock,
  }),
}))

import Component from './ThemePreferences.vue'

describe('themeList.vue', () => {
  const h = createHarness({
    beforeEach: () => openModalMock.mockClear(),
  })

  it('renders the list of built-in themes', () => {
    h.render(Component)

    screen.getByTestId('built-in-themes')
    expect(screen.queryAllByTestId('theme-card')).toHaveLength(themes.length)
    expect(screen.queryByTestId('custom-themes')).toBeNull()
  })

  it('renders custom themes and other elements for Plus edition', async () => {
    await h.withPlusEdition(async () => {
      h.mock(themeStore, 'fetchCustomThemes').mockImplementation(() => {
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
      await assertOpenModal(openModalMock, CreateThemeForm)
    })
  })
})
