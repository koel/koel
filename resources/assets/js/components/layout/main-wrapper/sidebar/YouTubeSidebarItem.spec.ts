import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './YouTubeSidebarItem.vue'

describe('youTubeSidebarItem.vue', () => {
  const h = createHarness()

  it('renders', async () => {
    const { html } = h.render(Component, {
      slots: {
        default: 'Another One Bites the Dust',
      },
    })

    expect(html()).toMatchSnapshot()
  })
})
