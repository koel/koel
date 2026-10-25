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

  it('closes on Escape', async () => {
    renderComponent()
    openLyricsOverlay()
    await waitFor(() => screen.getByTestId('lyrics-overlay'))

    await h.user.keyboard('{Escape}')

    expect(isLyricsOverlayOpen.value).toBe(false)
  })

  it('closes when the current playable is not a song', async () => {
    const { currentStreamable } = renderComponent()
    openLyricsOverlay()
    await waitFor(() => screen.getByTestId('lyrics-overlay'))

    currentStreamable.value = h.factory('episode').make()

    await waitFor(() => expect(isLyricsOverlayOpen.value).toBe(false))
  })
})
