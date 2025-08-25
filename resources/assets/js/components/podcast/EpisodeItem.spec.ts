import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { playbackService } from '@/services/QueuePlaybackService'
import { preferenceStore } from '@/stores/preferenceStore'
import Component from './EpisodeItem.vue'

describe('episodeItem.vue', () => {
  const h = createHarness()

  const renderComponent = (episode?: Episode, podcast?: Podcast) => {
    episode = episode || h.factory('episode')
    podcast = podcast || h.factory('podcast', { id: episode.podcast_id })

    const rendered = h.render(Component, {
      props: {
        episode,
        podcast,
      },
      global: {
        stubs: {
          EpisodeProgress: h.stub('episode-progress-stub'),
        },
      },
    })

    return {
      ...rendered,
      episode,
      podcast,
    }
  }

  it('pauses playback', async () => {
    h.createAudioPlayer()

    const pauseMock = h.mock(playbackService, 'pause')
    renderComponent(h.factory('episode', { playback_state: 'Playing' }))

    await h.user.click(screen.getByTestId('play-button'))

    expect(pauseMock).toHaveBeenCalled()
  })

  it('resumes playback', async () => {
    h.createAudioPlayer()

    const resumeMock = h.mock(playbackService, 'resume')
    renderComponent(h.factory('episode', { playback_state: 'Paused' }))

    await h.user.click(screen.getByTestId('play-button'))

    expect(resumeMock).toHaveBeenCalled()
  })

  it.each([[600, 50, 50], [600, 650, 0], [600, null, 0]])(
    'plays without continuous playback',
    async (episodeLength, currentPosition, startPlaybackPosition) => {
      h.createAudioPlayer()

      preferenceStore.temporary.continuous_playback = false
      const playMock = h.mock(playbackService, 'play')

      const episode = h.factory('episode', {
        length: episodeLength,
      })

      const podcast = h.factory('podcast', {
        id: episode.podcast_id,
        state: {
          current_episode: episode.id,
          progresses: {
            [episode.id]: currentPosition,
          },
        },
      })

      renderComponent(episode, podcast)
      await h.user.click(screen.getByTestId('play-button'))

      expect(playMock).toHaveBeenCalledWith(episode, startPlaybackPosition)
    },
  )

  it('plays from beginning if no saved progress', async () => {
    h.createAudioPlayer()

    const playMock = h.mock(playbackService, 'play')

    const { episode } = renderComponent()
    await h.user.click(screen.getByTestId('play-button'))

    expect(playMock).toHaveBeenCalledWith(episode, 0)
  })

  it('shows progress bar if there is progress', async () => {
    h.createAudioPlayer()

    const episode = h.factory('episode', {
      length: 300,
    })

    const podcast = h.factory('podcast', {
      id: episode.podcast_id,
      state: {
        current_episode: episode.id,
        progresses: {
          [episode.id]: 100,
        },
      },
    })

    renderComponent(episode, podcast)
    screen.getByTestId('episode-progress-stub')
  })

  it('does not show progress bar if no progress', async () => {
    renderComponent()
    expect(screen.queryByTestId('episode-progress-stub')).toBeNull()
  })
})
