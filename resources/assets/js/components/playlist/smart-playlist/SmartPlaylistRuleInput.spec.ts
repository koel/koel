import { describe, expect, it } from 'vite-plus/test'
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
})
