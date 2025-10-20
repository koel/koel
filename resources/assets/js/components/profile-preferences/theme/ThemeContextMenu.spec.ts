import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { screen } from '@testing-library/vue'
import { themeStore } from '@/stores/themeStore'
import Component from './ThemeContextMenu.vue'

describe('themeContextMenu.vue', () => {
  const h = createHarness()

  const renderComponent = (theme?: Theme) => {
    theme = theme || h.factory('theme')

    const rendered = h.actingAsAdmin().render(Component, {
      props: {
        theme,
      },
    })

    return {
      ...rendered,
      theme,
    }
  }

  it('applies a theme', async () => {
    const applyMock = h.mock(themeStore, 'setTheme')
    const { theme } = renderComponent()

    await h.user.click(screen.getByText('Apply Theme'))

    expect(applyMock).toHaveBeenCalledWith(theme)
  })

  it('deletes custom theme', async () => {
    const destroyMock = h.mock(themeStore, 'destroy')
    const { theme } = renderComponent()

    await h.user.click(screen.getByText('Delete'))

    expect(destroyMock).toHaveBeenCalledWith(theme)
  })

  it('does not have an option to delete built-in theme', async () => {
    renderComponent(h.factory('theme', { is_custom: false }))
    expect(screen.queryByText('Delete')).toBeNull()
  })
})
