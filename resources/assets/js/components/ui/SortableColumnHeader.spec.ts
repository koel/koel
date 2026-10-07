import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './SortableColumnHeader.vue'

describe('sortableColumnHeader.vue', () => {
  const h = createHarness()

  const renderComponent = (active: boolean, order: SortOrder = 'asc') =>
    h.render(Component, {
      props: { active, order },
      slots: { default: 'Name' },
      global: { stubs: { Icon: FontAwesomeIcon } },
    })

  it('asks to sort when clicked', async () => {
    const { emitted } = renderComponent(false)

    await h.user.click(screen.getByRole('button', { name: 'Name' }))

    expect(emitted().sort).toHaveLength(1)
  })

  it('asks to sort when Enter or Space is pressed', async () => {
    const { emitted } = renderComponent(false)

    screen.getByRole('button', { name: 'Name' }).focus()
    await h.user.keyboard('{Enter}')
    await h.user.keyboard(' ')

    expect(emitted().sort).toHaveLength(2)
  })

  it.each<[SortOrder, string]>([
    ['asc', 'caret-up'],
    ['desc', 'caret-down'],
  ])('shows the %s arrow when sorted by this column', (order, icon) => {
    const { container } = renderComponent(true, order)

    expect(container.querySelector(`[data-icon="${icon}"]`)).not.toBeNull()
  })

  it('shows no arrow when not sorted by this column', () => {
    const { container } = renderComponent(false)

    expect(container.querySelector('[data-icon^="caret"]')).toBeNull()
  })
})
