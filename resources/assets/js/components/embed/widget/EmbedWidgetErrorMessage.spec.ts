import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './EmbedWidgetErrorMessage.vue'

describe('EmbedWidgetErrorMessage', () => {
  const h = createHarness()

  it('renders the error message', () => {
    h.render(Component)
    expect(screen.getByText('Content not available')).toBeTruthy()
  })
})
