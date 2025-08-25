import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './BtnGroup.vue'
import Btn from './Btn.vue'

describe('btnGroup.vue', () => {
  const h = createHarness()

  const renderButtonToSlot = (text: string) => {
    return h.render(Btn, {
      slots: {
        default: text,
      },
    }).html()
  }

  it('renders', () => {
    expect(h.render(Component, {
      slots: {
        default: ['Green', 'Orange', 'Blue'].map(text => renderButtonToSlot(text)),
      },
    }).html()).toMatchSnapshot()
  })
})
