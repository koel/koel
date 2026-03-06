import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './SmartPlaylistRuleInput.vue'

describe('smartPlaylistRuleInput', () => {
  const h = createHarness()

  const renderComponent = (type: 'text' | 'number' | 'date', value?: any) => {
    return h.render(Component, {
      props: {
        type,
        value,
      },
    })
  }

  it('renders a text input', () => {
    renderComponent('text', 'foo')
    expect(screen.getByDisplayValue('foo').getAttribute('type')).toBe('text')
  })

  it('renders a number input', () => {
    renderComponent('number', '42')
    expect(screen.getByDisplayValue('42').getAttribute('type')).toBe('number')
  })

  it('renders a date input', () => {
    renderComponent('date', '2024-01-01')
    expect(screen.getByDisplayValue('2024-01-01').getAttribute('type')).toBe('date')
  })

  it('emits update:modelValue when value changes', async () => {
    const { emitted } = renderComponent('text')
    await h.type(screen.getByRole('textbox'), 'bar')

    expect(emitted()['update:modelValue']).toBeTruthy()
  })
})
