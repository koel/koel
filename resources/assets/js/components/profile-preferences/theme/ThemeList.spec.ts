import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { screen } from '@testing-library/vue'
import Component from './ThemeList.vue'

describe('themeList.vue', () => {
  const h = createHarness()

  it('renders a list of themes', async () => {
    h.render(Component, {
      props: {
        themes: h.factory('theme', 9),
      },
      global: {
        stubs: {
          ThemeCard: h.stub('theme-card'),
        },
      },
    })

    expect(screen.queryAllByTestId('theme-card')).toHaveLength(9)
  })
})
