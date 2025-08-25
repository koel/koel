import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { playbackService } from '@/services/QueuePlaybackService'
import { playableStore } from '@/stores/playableStore'
import Component from './PlayableCard.vue'

describe('playableCard.vue', () => {
  const h = createHarness()

  const renderComponent = (
    playbackState: PlaybackState = 'Stopped',
    mockFavoriteButton = true,
  ) => {
    const playable = h.factory('song', {
      playback_state: playbackState,
      play_count: 10,
      title: 'Foo bar',
      favorite: false,
    })

    const stubs = {
      PlayableThumbnail: h.stub('thumbnail'),
    }

    if (mockFavoriteButton) {
      stubs.FavoriteButton = h.stub('favorite-button')
    }

    const rendered = h.render(Component, {
      props: {
        playable,
      },
      global: {
        stubs,
      },
    })

    return {
      ...rendered,
      playable,
    }
  }

  it('has a thumbnail and a Favorite button', () => {
    renderComponent()
    screen.getByTestId('thumbnail')
    screen.getByTestId('favorite-button')
  })

  it('toggles the favorite state when the Favorite button is clicked', async () => {
    const { playable } = renderComponent('Stopped', false)
    const toggleFavoriteMock = h.mock(playableStore, 'toggleFavorite')

    await h.user.click(screen.getByRole('button', { name: 'Favorite' }))

    expect(toggleFavoriteMock).toHaveBeenCalledWith(playable)
  })

  it('queues and plays on double-click', async () => {
    h.createAudioPlayer()

    const playMock = h.mock(playbackService, 'play')
    const { playable } = renderComponent()

    await h.user.dblClick(screen.getByRole('article'))

    expect(playMock).toHaveBeenCalledWith(playable)
  })
})
