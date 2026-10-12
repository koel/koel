import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { themeStore } from '@/stores/themeStore'
import { screen } from '@testing-library/vue'
import Component from './ThemeSelectBox.vue'

describe('themeSelectBox.vue', () => {
  const h = createHarness()

  it('fetches and renders the options', async () => {
    const theme = h.factory('theme').make({
      id: 'frodo',
      name: 'One Theme to Rule Them All',
    })

    const fetchThemesMock = h.mock(themeStore, 'fetchCustomThemes')

    h.render(Component)

    // since the mock overrides the actual implementation, we manually mutate the store
    themeStore.state.themes.push(theme)

    await h.tick()

    expect(fetchThemesMock).toHaveBeenCalled()
    await h.user.selectOptions(screen.getByRole('combobox'), ['frodo'])
  })

  it('leaves out the Kameleon theme, which only follows the main player', async () => {
    h.mock(themeStore, 'fetchCustomThemes')

    h.render(Component)
    await h.tick()

    expect(screen.queryByRole('option', { name: 'Kameleon' })).toBeNull()
  })
})
