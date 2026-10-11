import { describe, expect, it } from 'vite-plus/test'
import { screen, within } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './BasicListSorter.vue'

describe('basicListSorter', () => {
  const h = createHarness()

  const items = [
    { label: 'Name', field: 'name' },
    { label: 'Date', field: 'date' },
    { label: 'Size', field: 'size' },
  ]

  const renderComponent = (field = 'name', order: SortOrder = 'asc') => {
    return h.render(Component, {
      props: { items, field, order },
    })
  }

  it('renders the current sort label in the button', () => {
    renderComponent('name', 'asc')
    const button = screen.getByRole('button')
    expect(button.textContent).toContain('Name')
  })

  it('shows ascending icon when order is asc', () => {
    renderComponent('name', 'asc')
    const button = screen.getByRole('button')
    expect(button.textContent).toContain('ascending')
  })

  it('renders all sort options in dropdown', () => {
    renderComponent()
    const menu = within(screen.getByRole('list'))
    menu.getByText('Name')
    menu.getByText('Date')
    menu.getByText('Size')
  })

  it('marks the current field as active', () => {
    renderComponent('date', 'asc')
    const dateItem = within(screen.getByRole('list')).getByText('Date').closest('li')!
    expect(dateItem.classList.contains('active')).toBe(true)
  })

  it('emits sort with toggled order when clicking current field', async () => {
    const { emitted } = renderComponent('name', 'asc')

    await h.user.click(within(screen.getByRole('list')).getByText('Name'))

    expect(emitted().sort).toBeTruthy()
    expect(emitted().sort[0]).toEqual(['name', 'desc'])
  })

  it('emits sort with asc when clicking a different field', async () => {
    const { emitted } = renderComponent('name', 'asc')

    await h.user.click(within(screen.getByRole('list')).getByText('Date'))

    expect(emitted().sort).toBeTruthy()
    expect(emitted().sort[0]).toEqual(['date', 'asc'])
  })

  it('tells the order in the button when it is descending', () => {
    renderComponent('size', 'desc')
    expect(screen.getByRole('button').textContent).toContain('descending')
  })
})
