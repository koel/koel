import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './ScreenHeader.vue'

describe('screenHeader.vue', () => {
  const h = createHarness()

  it('renders', () => {
    expect(h.render(Component, {
      slots: {
        default: 'This Header',
        meta: '<p>Some meta</p>',
        controls: '<nav>Some controls</nav>',
        thumbnail: '<img src="https://placekitten.com/200/300" />',
      },
    }).html()).toMatchSnapshot()
  })
})
