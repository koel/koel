import { describe, expect, it, vi } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './BasicListSorter.vue'

vi.mock('@/composables/useFloatingUi', () => ({
  useFloatingUi: () => ({
    setup: vi.fn(),
    teardown: vi.fn(),
    trigger: vi.fn(),
    hide: vi.fn(),
  }),
}))

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
    expect(button.getAttribute('title')).toContain('ascending')
  })

  it('shows descending in title when order is desc', () => {
    renderComponent('name', 'desc')
    const button = screen.getByRole('button')
    expect(button.getAttribute('title')).toContain('descending')
  })

  it('renders all sort options in dropdown', () => {
    renderComponent()
    screen.getByTitle('Sort by Name')
    screen.getByTitle('Sort by Date')
    screen.getByTitle('Sort by Size')
  })

  it('marks the current field as active', () => {
    renderComponent('date', 'asc')
    const dateItem = screen.getByTitle('Sort by Date')
    expect(dateItem.classList.contains('active')).toBe(true)
  })

  it('emits sort with toggled order when clicking current field', async () => {
    const { emitted } = renderComponent('name', 'asc')

    await h.user.click(screen.getByTitle('Sort by Name'))

    expect(emitted().sort).toBeTruthy()
    expect(emitted().sort[0]).toEqual(['name', 'desc'])
  })

  it('emits sort with asc when clicking a different field', async () => {
    const { emitted } = renderComponent('name', 'asc')

    await h.user.click(screen.getByTitle('Sort by Date'))

    expect(emitted().sort).toBeTruthy()
    expect(emitted().sort[0]).toEqual(['date', 'asc'])
  })

  it('includes sort field in button title', () => {
    renderComponent('size', 'desc')
    const button = screen.getByRole('button')
    expect(button.getAttribute('title')).toBe('Sorting by Size, descending')
  })
})
