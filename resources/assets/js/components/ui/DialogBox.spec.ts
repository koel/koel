import { describe, expect, it, vi } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './DialogBox.vue'

describe('dialogBox', () => {
  const h = createHarness({
    beforeEach: () => {
      HTMLDialogElement.prototype.showModal = vi.fn()
      HTMLDialogElement.prototype.close = vi.fn()
    },
  })

  const renderComponent = () => {
    return h.render(Component)
  }

  it('renders OK button', () => {
    renderComponent()
    screen.getByRole('button', { name: 'OK', hidden: true })
  })

  it('does not show Cancel button by default', () => {
    renderComponent()
    expect(screen.queryByRole('button', { name: 'Cancel', hidden: true })).toBeNull()
  })

  it('renders a dialog element', () => {
    renderComponent()
    expect(document.querySelector('dialog')).toBeTruthy()
  })

  it('has info class by default', () => {
    renderComponent()
    expect(document.querySelector('dialog.info')).toBeTruthy()
  })
})
