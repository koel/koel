import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './AlertBox.vue'

describe('AlertBox', () => {
  const h = createHarness()

  it('renders default type with slot content', () => {
    h.render(Component, { slots: { default: 'Hello world' } })
    screen.getByText('Hello world')
  })

  it('applies type-based CSS class', () => {
    const { container } = h.render(Component, { props: { type: 'danger' } })
    expect(container.querySelector('.alert-box-danger')).toBeTruthy()
  })

  it.each(['info', 'danger', 'success', 'warning'] as const)('applies %s type class', type => {
    const { container } = h.render(Component, { props: { type } })
    expect(container.querySelector(`.alert-box-${type}`)).toBeTruthy()
  })

  it('defaults to "default" type', () => {
    const { container } = h.render(Component)
    expect(container.querySelector('.alert-box-default')).toBeTruthy()
  })
})
