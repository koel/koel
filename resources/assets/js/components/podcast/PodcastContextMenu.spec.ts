import Router from '@/router'
import { expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { eventBus } from '@/utils/eventBus'
import { playbackService } from '@/services/playbackService'
import { songStore as episodeStore } from '@/stores/songStore'
import Component from './PodcastContextMenu.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders', async () => expect((await this.renderComponent()).html()).toMatchSnapshot())

    it('plays all', async () => {
      const episodes = factory('episode', 10)
      const fetchMock = this.mock(episodeStore, 'fetchForPodcast').mockResolvedValue(episodes)
      const playMock = this.mock(playbackService, 'queueAndPlay')

      const { podcast } = await this.renderComponent()
      await this.user.click(screen.getByText('Play All'))
      await this.tick()

      expect(fetchMock).toHaveBeenCalledWith(podcast)
      expect(playMock).toHaveBeenCalledWith(episodes)
    })

    it('shuffles all', async () => {
      const episodes = factory('episode', 10)
      const fetchMock = this.mock(episodeStore, 'fetchForPodcast').mockResolvedValue(episodes)
      const playMock = this.mock(playbackService, 'queueAndPlay')

      const { podcast } = await this.renderComponent()
      await this.user.click(screen.getByText('Shuffle All'))
      await this.tick()

      expect(fetchMock).toHaveBeenCalledWith(podcast)
      expect(playMock).toHaveBeenCalledWith(episodes, true)
    })

    it('goes to podcast', async () => {
      const mock = this.mock(Router, 'go')
      const { podcast } = await this.renderComponent()

      await this.user.click(screen.getByText('Go to Podcast'))

      expect(mock).toHaveBeenCalledWith(`/#/podcasts/${podcast.id}`)
    })
  }

  private async renderComponent (podcast?: Podcast) {
    podcast = podcast || factory('podcast', {
      title: 'A Brief History of Time',
      author: 'Stephen Hawking',
      favorite: false,
    })

    const rendered = this.beAdmin().render(Component)
    eventBus.emit('PODCAST_CONTEXT_MENU_REQUESTED', { pageX: 420, pageY: 42 } as MouseEvent, podcast)
    await this.tick(2)

    return {
      ...rendered,
      podcast,
    }
  }
}
