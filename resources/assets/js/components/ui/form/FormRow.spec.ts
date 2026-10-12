import { describe, expect, it } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './FormRow.vue'

describe('formRow.vue', () => {
  const h = createHarness()

  it('labels the field in a single column', () => {
    h.render(Component, {
      slots: {
        label: 'Name',
        default: '<input />',
      },
    })

    expect(screen.getByLabelText('Name')).toBe(screen.getByRole('textbox'))
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
