import { describe, expect, it } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './ContextMenuItem.vue'

describe('contextMenuItem', () => {
  const h = createHarness()

  it('renders submenu caret when subMenuItems slot is provided', () => {
    h.render(Component, {
      slots: {
        default: 'Add to...',
        subMenuItems: '<li>Playlist 1</li>',
      },
    })

    const li = screen.getByText('Add to...').closest('li')!
    expect(li.classList.contains('has-sub')).toBe(true)
  })

  it('renders icon slot when provided', () => {
    h.render(Component, {
      slots: {
        default: 'Play',
        icon: '<span data-testid="custom-icon">I</span>',
      },
    })

    screen.getByTestId('custom-icon')
  })

  it('does not have icon class without icon slot', () => {
    h.render(Component, {
      slots: { default: 'Play' },
    })

    const li = screen.getByText('Play').closest('li')!
    expect(li.classList.contains('flex')).toBe(false)
  })

  it('tells assistive tech that an item opens a submenu', () => {
    h.render(Component, {
      slots: {
        default: 'Add to...',
        subMenuItems: '<li>Playlist 1</li>',
      },
    })

    const item = screen.getByRole('menuitem')
    expect(item.getAttribute('aria-haspopup')).toBe('menu')
    expect(item.getAttribute('aria-expanded')).toBe('false')
  })

  it('does not mark a plain item as opening a submenu', () => {
    h.render(Component, {
      slots: { default: 'Play' },
    })

    const item = screen.getByRole('menuitem')
    expect(item.hasAttribute('aria-haspopup')).toBe(false)
    expect(item.hasAttribute('aria-expanded')).toBe(false)
  })
})
