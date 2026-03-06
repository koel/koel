import { describe, expect, it, vi } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './Overlay.vue'

describe('overlay', () => {
  const h = createHarness({
    beforeEach: () => {
      HTMLDialogElement.prototype.showModal = vi.fn()
      HTMLDialogElement.prototype.close = vi.fn()
    },
  })

  it('renders dialog element with testid', () => {
    h.render(Component)
    expect(screen.getByTestId('overlay')).toBeTruthy()
  })

  it('renders with loading type by default', () => {
    h.render(Component)
    const dialog = screen.getByTestId('overlay')
    expect(dialog.classList.contains('loading')).toBe(true)
  })

  it('renders as a dialog element', () => {
    h.render(Component)
    const dialog = screen.getByTestId('overlay')
    expect(dialog.tagName).toBe('DIALOG')
  })
})
