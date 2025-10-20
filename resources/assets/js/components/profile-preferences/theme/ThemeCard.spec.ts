import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './ThemeCard.vue'
import { themeStore } from '@/stores/themeStore'

describe('themeCard.vue', () => {
  const h = createHarness()

  const renderComponent = () => {
    const theme = h.factory('theme', {
      name: 'Sample',
    })

    const rendered = h.render(Component, {
      props: {
        theme,
      },
    })

    return {
      ...rendered,
      theme,
    }
  }

  it('renders', () => expect(renderComponent().html()).toMatchSnapshot())

  it('sets the theme when clicked', async () => {
    const { theme } = renderComponent()
    const setThemeMock = h.mock(themeStore, 'setTheme')

    await h.user.click(screen.getByRole('button', { name: 'Sample' }))

    expect(setThemeMock).toHaveBeenCalledWith(theme)
  })
})
