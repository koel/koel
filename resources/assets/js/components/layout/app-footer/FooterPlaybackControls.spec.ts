import { ref } from 'vue'
import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { CurrentStreamableKey } from '@/symbols'
import { playbackService } from '@/services/QueuePlaybackService'
import Component from './FooterPlaybackControls.vue'

describe('footerPlaybackControls.vue', () => {
  const h = createHarness()

  const renderComponent = (playable?: Playable | null) => {
    if (playable === undefined) {
      playable = h.factory('song', {
        id: '00000000-0000-0000-0000-000000000000',
        title: 'Fahrstuhl to Heaven',
        artist_name: 'Led Zeppelin',
        artist_id: 'led-zeppelin',
        album_name: 'Led Zeppelin IV',
        album_id: 'iv',
        favorite: true,
      })
    }

    return h.render(Component, {
      global: {
        stubs: {
          PlayButton: h.stub('PlayButton'),
        },
        provide: {
          [<symbol>CurrentStreamableKey]: ref(playable),
        },
      },
    })
  }

  it('renders without a current playable', () => expect(renderComponent(null).html()).toMatchSnapshot())
  it('renders with a current playable', () => expect(renderComponent().html()).toMatchSnapshot())

  it('plays the previous playable', async () => {
    h.createAudioPlayer()

    const playMock = h.mock(playbackService, 'playPrev')
    renderComponent()

    await h.user.click(screen.getByRole('button', { name: 'Play previous in queue' }))

    expect(playMock).toHaveBeenCalled()
  })

  it('plays the next playable', async () => {
    h.createAudioPlayer()

    const playMock = h.mock(playbackService, 'playNext')
    renderComponent()

    await h.user.click(screen.getByRole('button', { name: 'Play next in queue' }))

    expect(playMock).toHaveBeenCalled()
  })
})
