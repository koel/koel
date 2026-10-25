import { screen, waitFor } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { ref } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import { CurrentStreamableKey } from '@/config/symbols'
import { useLyricsOverlay } from '@/composables/useLyricsOverlay'
import Component from './LyricsOverlay.vue'

describe('lyricsOverlay.vue', () => {
  const { isLyricsOverlayOpen, openLyricsOverlay, closeLyricsOverlay } = useLyricsOverlay()

  const h = createHarness({
    beforeEach: () => closeLyricsOverlay(),
  })

  const renderComponent = (streamable: Streamable = h.factory('song').make({ lyrics: 'Foo' })) => {
    const currentStreamable = ref<Streamable | undefined>(streamable)

    h.render(Component, {
      global: {
        provide: {
          [<symbol>CurrentStreamableKey]: currentStreamable,
        },
        stubs: {
          LyricsPane: h.stub('lyrics-pane'),
        },
      },
    })

    return { currentStreamable }
  }

  it('shows the lyrics when opened', async () => {
    renderComponent()
    openLyricsOverlay()

    await waitFor(() => screen.getByTestId('lyrics-pane'))
  })

  it('closes on Escape when focus is outside the overlay', async () => {
    renderComponent()
    openLyricsOverlay()
    const overlay = await waitFor(() => screen.getByRole('region', { name: 'Lyrics' }))
    overlay.blur()

    await h.user.keyboard('{Escape}')

    expect(isLyricsOverlayOpen.value).toBe(false)
  })

  it('stays open on Escape inside a dialog', async () => {
    renderComponent()
    openLyricsOverlay()
    await waitFor(() => screen.getByRole('region', { name: 'Lyrics' }))

    const dialog = document.createElement('dialog')
    const input = document.createElement('input')
    dialog.appendChild(input)
    document.body.appendChild(dialog)
    input.focus()

    await h.user.keyboard('{Escape}')

    expect(isLyricsOverlayOpen.value).toBe(true)

    dialog.remove()
  })

  it('closes when the current playable is not a song', async () => {
    const { currentStreamable } = renderComponent()
    openLyricsOverlay()
    await waitFor(() => screen.getByRole('region', { name: 'Lyrics' }))

    currentStreamable.value = h.factory('episode').make()

    await waitFor(() => expect(isLyricsOverlayOpen.value).toBe(false))
  })

  it('closes when the next song has no lyrics', async () => {
    const { currentStreamable } = renderComponent()
    openLyricsOverlay()
    await waitFor(() => screen.getByRole('region', { name: 'Lyrics' }))

    currentStreamable.value = h.factory('song').make({ lyrics: '' })

    await waitFor(() => expect(isLyricsOverlayOpen.value).toBe(false))
  })
})
