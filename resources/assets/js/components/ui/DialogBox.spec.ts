import { describe, expect, it, vi } from 'vite-plus/test'
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

  it('does not show Cancel button by default', () => {
    renderComponent()
    expect(screen.queryByRole('button', { name: 'Cancel', hidden: true })).toBeNull()
  })

  it('has info class by default', () => {
    renderComponent()
    expect(document.querySelector('dialog.info')).toBeTruthy()
  })
})
