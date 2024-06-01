import { screen } from '@testing-library/vue'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import Component from './EpisodeItem.vue'
import factory from '@/__tests__/factory'
import { playbackService } from '@/services'

new class extends UnitTestCase {
  private renderComponent (episode: Episode, podcast?: Podcast) {
    podcast = podcast || factory('podcast', {
      id: episode.id
    })

    this.render(Component, {
      props: {
        episode,
        podcast
      }
    })
  }

  protected test () {
    it('pauses playback', async () => {
      const pauseMock = this.mock(playbackService, 'pause')

      const episode = factory('episode', {
        id: 'foo',
        playback_state: 'Playing'
      })

      this.renderComponent(episode)

      await this.user.click(screen.getByRole('button'))

      expect(pauseMock).toHaveBeenCalled()
    })
  }
}
