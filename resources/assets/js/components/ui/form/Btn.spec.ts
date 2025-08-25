import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './Btn.vue'

describe('btn.vue', () => {
  const h = createHarness()

  it('renders', () => {
    expect(h.render(Component, {
      slots: {
        default: 'Click Me Nao',
      },
    }).html()).toMatchSnapshot()
  })
})
