import { describe, expect, it } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
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

  describe('keyboard navigation', () => {
    const openMenu = async (field: string = 'name', order: SortOrder = 'asc') => {
      const rendered = renderComponent(field, order)
      const panel = rendered.container.querySelector<HTMLElement>('[popover]')!

      panel.showPopover()
      await h.tick(2)

      return rendered
    }

    it('focuses the active sort field when the menu opens', async () => {
      await openMenu('date', 'asc')

      expect(document.activeElement).toBe(screen.getByTitle('Sort by Date'))
    })

    it('falls back to the first item when no field matches', async () => {
      await openMenu('unknown' as any, 'asc')

      expect(document.activeElement).toBe(screen.getByTitle('Sort by Name'))
    })

    it('moves focus to the next item on ArrowDown', async () => {
      await openMenu('name', 'asc')

      await h.user.keyboard('{ArrowDown}')

      expect(document.activeElement).toBe(screen.getByTitle('Sort by Date'))
    })

    it('wraps focus to the first item past the end', async () => {
      await openMenu('size', 'asc')

      await h.user.keyboard('{ArrowDown}')

      expect(document.activeElement).toBe(screen.getByTitle('Sort by Name'))
    })

    it('moves focus to the previous item on ArrowUp', async () => {
      await openMenu('date', 'asc')

      await h.user.keyboard('{ArrowUp}')

      expect(document.activeElement).toBe(screen.getByTitle('Sort by Name'))
    })

    it('wraps focus to the last item past the start', async () => {
      await openMenu('name', 'asc')

      await h.user.keyboard('{ArrowUp}')

      expect(document.activeElement).toBe(screen.getByTitle('Sort by Size'))
    })

    it('jumps to the first item on Home', async () => {
      await openMenu('size', 'asc')

      await h.user.keyboard('{Home}')

      expect(document.activeElement).toBe(screen.getByTitle('Sort by Name'))
    })

    it('jumps to the last item on End', async () => {
      await openMenu('name', 'asc')

      await h.user.keyboard('{End}')

      expect(document.activeElement).toBe(screen.getByTitle('Sort by Size'))
    })

    it('activates the focused item on Enter', async () => {
      const { emitted } = await openMenu('name', 'asc')

      await h.user.keyboard('{ArrowDown}')
      await h.user.keyboard('{Enter}')

      expect(emitted().sort).toBeTruthy()
      expect(emitted().sort[0]).toEqual(['date', 'asc'])
    })

    it('activates the focused item on Space', async () => {
      const { emitted } = await openMenu('name', 'asc')

      await h.user.keyboard('{ArrowDown}{ArrowDown}')
      await h.user.keyboard(' ')

      expect(emitted().sort).toBeTruthy()
      expect(emitted().sort[0]).toEqual(['size', 'asc'])
    })

    it('uses roving tabindex on menu items', async () => {
      await openMenu('date', 'asc')

      const dateItem = screen.getByTitle('Sort by Date')
      const nameItem = screen.getByTitle('Sort by Name')
      const sizeItem = screen.getByTitle('Sort by Size')

      expect(dateItem.getAttribute('tabindex')).toBe('0')
      expect(nameItem.getAttribute('tabindex')).toBe('-1')
      expect(sizeItem.getAttribute('tabindex')).toBe('-1')

      await h.user.keyboard('{ArrowDown}')

      expect(dateItem.getAttribute('tabindex')).toBe('-1')
      expect(sizeItem.getAttribute('tabindex')).toBe('0')
    })
  })
})
