import { screen } from '@testing-library/vue'
import { describe, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './EmbedWidgetErrorMessage.vue'

describe('EmbedWidgetErrorMessage', () => {
  const h = createHarness()

  it('renders the error message', () => {
    h.render(Component)
    screen.getByText('Content not available')
  })
})
