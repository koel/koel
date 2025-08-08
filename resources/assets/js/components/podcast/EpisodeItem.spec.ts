import { screen } from '@testing-library/vue'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { playbackService } from '@/services/QueuePlaybackService'
import { preferenceStore } from '@/stores/preferenceStore'
import Component from './EpisodeItem.vue'

new class extends UnitTestCase {
  private renderComponent (episode?: Episode, podcast?: Podcast) {
    episode = episode || factory('episode')
    podcast = podcast || factory('podcast', { id: episode.podcast_id })

    const rendered = this.render(Component, {
      props: {
        episode,
        podcast,
      },
      global: {
        stubs: {
          EpisodeProgress: this.stub('episode-progress-stub'),
        },
      },
    })

    return {
      ...rendered,
      episode,
      podcast,
    }
  }

  protected test () {
    it('pauses playback', async () => {
      this.createAudioPlayer()

      const pauseMock = this.mock(playbackService, 'pause')
      this.renderComponent(factory('episode', { playback_state: 'Playing' }))

      await this.user.click(screen.getByTestId('play-button'))

      expect(pauseMock).toHaveBeenCalled()
    })

    it('resumes playback', async () => {
      this.createAudioPlayer()

      const resumeMock = this.mock(playbackService, 'resume')
      this.renderComponent(factory('episode', { playback_state: 'Paused' }))

      await this.user.click(screen.getByTestId('play-button'))

      expect(resumeMock).toHaveBeenCalled()
    })

    it.each([[600, 50, 50], [600, 650, 0], [600, null, 0]])(
      'plays without continuous playback',
      async (episodeLength, currentPosition, startPlaybackPosition) => {
        this.createAudioPlayer()

        preferenceStore.temporary.continuous_playback = false
        const playMock = this.mock(playbackService, 'play')

        const episode = factory('episode', {
          length: episodeLength,
        })

        const podcast = factory('podcast', {
          id: episode.podcast_id,
          state: {
            current_episode: episode.id,
            progresses: {
              [episode.id]: currentPosition,
            },
          },
        })

        this.renderComponent(episode, podcast)
        await this.user.click(screen.getByTestId('play-button'))

        expect(playMock).toHaveBeenCalledWith(episode, startPlaybackPosition)
      },
    )

    it('plays from beginning if no saved progress', async () => {
      this.createAudioPlayer()

      const playMock = this.mock(playbackService, 'play')

      const { episode } = this.renderComponent()
      await this.user.click(screen.getByTestId('play-button'))

      expect(playMock).toHaveBeenCalledWith(episode, 0)
    })

    it('shows progress bar if there is progress', async () => {
      this.createAudioPlayer()

      const episode = factory('episode', {
        length: 300,
      })

      const podcast = factory('podcast', {
        id: episode.podcast_id,
        state: {
          current_episode: episode.id,
          progresses: {
            [episode.id]: 100,
          },
        },
      })

      this.renderComponent(episode, podcast)
      screen.getByTestId('episode-progress-stub')
    })

    it('does not show progress bar if no progress', async () => {
      this.renderComponent()
      expect(screen.queryByTestId('episode-progress-stub')).toBeNull()
    })
  }
}
