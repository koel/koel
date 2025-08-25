import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './ScreenEmptyState.vue'

describe('screenEmptyState.vue', () => {
  const h = createHarness()

  it('renders', () => {
    expect(h.render(Component, {
      slots: {
        icon: '<i class="my-icon"/>',
        default: 'Nothing here',
      },
    }).html()).toMatchSnapshot()
  })
})
