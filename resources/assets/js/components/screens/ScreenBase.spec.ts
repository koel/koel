import { describe, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './ScreenBase.vue'

describe('screenBase.vue', () => {
  const h = createHarness()

  it('renders header and default slots', () => {
    const { getByText } = h.render(Component, {
      slots: {
        header: 'Screen Header',
        default: 'Screen Content',
      },
    })

    getByText('Screen Header')
    getByText('Screen Content')
  })
})
