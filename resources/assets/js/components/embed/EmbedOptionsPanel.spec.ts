import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './EmbedOptionsPanel.vue'
import { screen, waitFor } from '@testing-library/vue'

describe('embedOptionsPanel.vue', () => {
  const h = createHarness()

  const renderComponent = () => {
    return h.render(Component, {
      props: {
        modelValue: {
          theme: 'classic',
          layout: 'full',
          preview: false,
        } satisfies EmbedOptions,
      },
      global: {
        stubs: {
          ThemeSelectBox: h.stub('theme-select'),
        },
      },
    })
  }

  it('does not have the theme option by default', () => {
    renderComponent()
    expect(screen.queryByTestId('theme-select')).toBeNull()
  })

  it('has the theme option in Plus edition', () => {
    h.withPlusEdition(async () => {
      renderComponent()
      await waitFor(() => screen.getByTestId('theme-select'))
    })
  })
})
