import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './FormRow.vue'

describe('formRow.vue', () => {
  const h = createHarness()

  it('renders single column with label and help slots', () => {
    const { html } = h.render(Component, {
      slots: {
        label: 'Name',
        default: '<input />',
        help: 'Enter your name',
      },
    })

    expect(html()).toMatchSnapshot()
  })

  it('renders multi-column grid', () => {
    const { container } = h.render(Component, {
      props: { cols: 2 },
      slots: { default: '<div>Col 1</div><div>Col 2</div>' },
    })

    expect(container.querySelector('.md\\:grid-cols-2')).not.toBeNull()
  })

  it('renders 3-column grid', () => {
    const { container } = h.render(Component, {
      props: { cols: 3 },
      slots: { default: '<div>Col</div>' },
    })

    expect(container.querySelector('.md\\:grid-cols-3')).not.toBeNull()
  })
})
