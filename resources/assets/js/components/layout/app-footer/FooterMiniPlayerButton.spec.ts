import { afterEach, beforeEach, describe, expect, it, vi } from 'vite-plus/test'
import { screen, waitFor } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './FooterMiniPlayerButton.vue'

describe('footerMiniPlayerButton.vue', () => {
  const h = createHarness()

  let miniPlayerDocument: Document
  let closeMiniPlayer: Closure

  const miniPlayerWindow = {
    close: vi.fn(() => closeMiniPlayer()),
    addEventListener: (_event: string, listener: Closure) => (closeMiniPlayer = listener),
  }

  beforeEach(() => {
    miniPlayerDocument = document.implementation.createHTMLDocument()

    h.setReadOnlyProperty(window, 'documentPictureInPicture', {
      requestWindow: vi.fn().mockResolvedValue({ ...miniPlayerWindow, document: miniPlayerDocument }),
    })
  })

  afterEach(() => {
    miniPlayerWindow.close.mockClear()
  })

  it('opens the mini player in its own window', async () => {
    h.render(Component)

    await h.user.click(screen.getByRole('button', { name: 'Open mini player' }))

    await waitFor(() => expect(miniPlayerDocument.querySelector('[role="slider"]')).not.toBeNull())
  })

  it('closes the mini player window', async () => {
    h.render(Component)

    await h.user.click(screen.getByRole('button', { name: 'Open mini player' }))
    await h.user.click(await screen.findByRole('button', { name: 'Close mini player' }))

    expect(miniPlayerWindow.close).toHaveBeenCalled()
    await waitFor(() => expect(miniPlayerDocument.querySelector('[role="slider"]')).toBeNull())
  })
})
