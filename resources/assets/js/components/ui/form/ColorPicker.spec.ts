import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './ColorPicker.vue'

describe('colorPicker.vue', () => {
  const h = createHarness()

  it('renders color input with current value', () => {
    const { container } = h.render(Component, {
      props: { modelValue: '#ff0000' },
      slots: { default: 'Pick color' },
    })

    const input = container.querySelector('input[type="color"]') as HTMLInputElement
    expect(input.value).toBe('#ff0000')
  })

  it('shows color preview swatch', () => {
    const { container } = h.render(Component, {
      props: { modelValue: '#00ff00' },
    })

    const swatch = container.querySelector('span[style]') as HTMLElement
    expect(swatch.style.backgroundColor).toBeTruthy()
  })
})
