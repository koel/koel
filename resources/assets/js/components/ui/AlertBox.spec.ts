import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './AlertBox.vue'

describe('AlertBox', () => {
  const h = createHarness()

  it.each(['info', 'danger', 'success', 'warning'] as const)('applies %s type class', type => {
    const { container } = h.render(Component, { props: { type } })
    expect(container.querySelector(`.alert-box-${type}`)).toBeTruthy()
  })

  it('defaults to "default" type', () => {
    const { container } = h.render(Component)
    expect(container.querySelector('.alert-box-default')).toBeTruthy()
  })
})
