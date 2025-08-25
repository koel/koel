import Router from '@/router'
import { expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { eventBus } from '@/utils/eventBus'
import { playbackService } from '@/services/QueuePlaybackService'
import { playableStore } from '@/stores/playableStore'
import Component from './GenreContextMenu.vue'
import { queueStore } from '@/stores/queueStore'

new class extends UnitTestCase {
  protected test () {
    it('renders', async () => expect((await this.renderComponent()).html()).toMatchSnapshot())

    it('plays all', async () => {
      this.createAudioPlayer()

      const songs = factory('song', 10)
      const fetchMock = this.mock(playableStore, 'fetchSongsByGenre').mockResolvedValue(songs)
      const playMock = this.mock(playbackService, 'queueAndPlay')

      const { genre } = await this.renderComponent()
      await this.user.click(screen.getByText('Play'))
      await this.tick()

      expect(fetchMock).toHaveBeenCalledWith(genre)
      expect(playMock).toHaveBeenCalledWith(songs)
    })

    it('shuffles all', async () => {
      this.createAudioPlayer()

      const songs = factory('song', 10)
      const fetchMock = this.mock(playableStore, 'fetchSongsByGenre').mockResolvedValue(songs)
      const playMock = this.mock(playbackService, 'queueAndPlay')

      const { genre } = await this.renderComponent()
      await this.user.click(screen.getByText('Shuffle'))
      await this.tick()

      expect(fetchMock).toHaveBeenCalledWith(genre, true)
      expect(playMock).toHaveBeenCalledWith(songs)
    })

    it('adds to queue', async () => {
      const songs = factory('song', 10)
      const fetchMock = this.mock(playableStore, 'fetchSongsByGenre').mockResolvedValue(songs)
      const queueMock = this.mock(queueStore, 'queue')

      const { genre } = await this.renderComponent()
      await this.user.click(screen.getByText('Add to Queue'))
      await this.tick()

      expect(fetchMock).toHaveBeenCalledWith(genre)
      expect(queueMock).toHaveBeenCalledWith(songs)
    })

    it('goes to genre', async () => {
      const mock = this.mock(Router, 'go')
      const { genre } = await this.renderComponent()

      await this.user.click(screen.getByText('View Genre'))

      expect(mock).toHaveBeenCalledWith(`/#/genres/${genre.id}`)
    })
  }

  private async renderComponent (genre?: Genre) {
    genre = genre || factory('genre', {
      name: 'Classical',
    })

    const rendered = this.beAdmin().render(Component)
    eventBus.emit('GENRE_CONTEXT_MENU_REQUESTED', { pageX: 420, pageY: 42 } as MouseEvent, genre)
    await this.tick(2)

    return {
      ...rendered,
      genre,
    }
  }
}
