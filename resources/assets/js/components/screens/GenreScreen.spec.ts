import { expect, it } from 'vitest'
import { screen, waitFor } from '@testing-library/vue'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { genreStore } from '@/stores/genreStore'
import { playableStore } from '@/stores/playableStore'
import { playbackService } from '@/services/QueuePlaybackService'
import Component from './GenreScreen.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders the song list', async () => {
      await this.renderComponent()
      expect(screen.getByTestId('song-list')).toBeTruthy()
    })

    it('shuffles all songs without fetching if genre has <= 500 songs', async () => {
      this.createAudioPlayer()

      const genre = factory('genre', { song_count: 10 })
      const songs = factory('song', 10)
      const playbackMock = this.mock(playbackService, 'queueAndPlay')

      await this.renderComponent(genre, songs)

      await this.user.click(screen.getByTitle('Shuffle all. Press Alt/⌥ to change mode.'))

      expect(playbackMock).toHaveBeenCalledWith(songs, true)
    })

    it('fetches and shuffles all songs if genre has > 500 songs', async () => {
      this.createAudioPlayer()

      const genre = factory('genre', { song_count: 501 })
      const songs = factory('song', 10) // we don't really need to generate 501 songs
      const playbackMock = this.mock(playbackService, 'queueAndPlay')
      const fetchMock = this.mock(playableStore, 'fetchRandomSongsByGenre').mockResolvedValue(songs)

      await this.renderComponent(genre, songs)

      await this.user.click(screen.getByTitle('Shuffle all. Press Alt/⌥ to change mode.'))

      await waitFor(() => {
        expect(fetchMock).toHaveBeenCalledWith(genre, 500)
        expect(playbackMock).toHaveBeenCalledWith(songs)
      })
    })
  }

  private async renderComponent (genre?: Genre, songs?: Song[]) {
    genre = genre || factory('genre')

    const fetchGenreMock = this.mock(genreStore, 'fetchOne').mockResolvedValue(genre)
    const paginateMock = this.mock(playableStore, 'paginateSongsForGenre').mockResolvedValue({
      nextPage: 2,
      songs: songs || factory('song', 13),
    })

    await this.router.activateRoute({
      path: `genres/${genre.id}`,
      screen: 'Genre',
    }, { id: genre.id })

    const rendered = this.render(Component, {
      global: {
        stubs: {
          SongList: this.stub('song-list'),
        },
      },
    })

    await waitFor(() => {
      expect(fetchGenreMock).toHaveBeenCalledWith(genre!.id)
      expect(paginateMock).toHaveBeenCalledWith(genre!.id, {
        sort: 'title',
        order: 'asc',
        page: 1,
      })
    })

    await this.tick(2)

    return rendered
  }
}
