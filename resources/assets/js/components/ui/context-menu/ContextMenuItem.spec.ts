import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './ContextMenuItem.vue'

describe('contextMenuItem', () => {
  const h = createHarness()

  it('renders the label from default slot', () => {
    h.render(Component, {
      slots: { default: 'Play' },
    })

    screen.getByText('Play')
  })

  it('emits click on click', async () => {
    const { emitted } = h.render(Component, {
      slots: { default: 'Play' },
    })

    await h.user.click(screen.getByText('Play'))

    expect(emitted().click).toBeTruthy()
  })

  it('emits click on Enter keydown', async () => {
    const { emitted } = h.render(Component, {
      slots: { default: 'Play' },
    })

    const li = screen.getByText('Play').closest('li')!
    await h.trigger(li, 'keyDown', { key: 'Enter' })

    expect(emitted().click).toBeTruthy()
  })

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
})
