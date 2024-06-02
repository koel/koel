import { screen } from '@testing-library/vue'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import Component from './EpisodeItem.vue'
import factory from '@/__tests__/factory'
import { playbackService } from '@/services'
import { preferenceStore } from '@/stores'

new class extends UnitTestCase {
  private renderComponent (episode: Episode, podcast?: Podcast) {
    podcast = podcast || factory('podcast', {
      id: episode.id
    })

    this.render(Component, {
      props: {
        episode,
        podcast
      },
      global: {
        stubs: {
          EpisodeProgress: this.stub('episode-progress-stub')
        }
      }
    })
  }

  protected test () {
    it('pauses playback', async () => {
      const pauseMock = this.mock(playbackService, 'pause')

      this.renderComponent(factory('episode', {
        id: 'foo',
        playback_state: 'Playing'
      }))

      await this.user.click(screen.getByRole('button'))

      expect(pauseMock).toHaveBeenCalled()
    })

    it('resumes playback', async () => {
      const resumeMock = this.mock(playbackService, 'resume')

      this.renderComponent(factory('episode', {
        id: 'foo',
        playback_state: 'Paused'
      }))

      await this.user.click(screen.getByRole('button'))

      expect(resumeMock).toHaveBeenCalled()
    })

    it.each([[600, 50, 50], [600, 650, 0], [600, null, 0]])(
      'plays without continuous playback', async (episodeLength, currentPosition, startPlaybackPosition) => {
        preferenceStore.temporary.continuous_playback = false
        const playMock = this.mock(playbackService, 'play')

        const episode = factory('episode', {
          length: episodeLength
        })

        const podcast = factory('podcast', {
          id: episode.podcast_id,
          state: {
            current_episode: episode.id,
            progresses: {
              [episode.id]: currentPosition
            }
          }
        })

        this.renderComponent(episode, podcast)
        await this.user.click(screen.getByRole('button'))

        expect(playMock).toHaveBeenCalledWith(episode, startPlaybackPosition)
      }
    )

    it('plays from beginning if no saved progress', async () => {
      const playMock = this.mock(playbackService, 'play')

      const episode = factory('episode')
      this.renderComponent(episode)
      await this.user.click(screen.getByRole('button'))

      expect(playMock).toHaveBeenCalledWith(episode, 0)
    })

    it('shows progress bar if there is progress', async () => {
      const episode = factory('episode', {
        length: 300
      })

      const podcast = factory('podcast', {
        id: episode.podcast_id,
        state: {
          current_episode: episode.id,
          progresses: {
            [episode.id]: 100
          }
        }
      })

      this.renderComponent(episode, podcast)
      screen.getByTestId('episode-progress-stub')
    })

    it('does not show progress bar if no progress', async () => {
      this.renderComponent(factory('episode'))
      expect(screen.queryByTestId('episode-progress-stub')).toBeNull()
    })
  }
}
