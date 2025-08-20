import { screen, waitFor } from '@testing-library/vue'
import { expect, it } from 'vitest'
import { orderBy } from 'lodash'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { queueStore } from '@/stores/queueStore'
import { playableStore } from '@/stores/playableStore'
import { playbackService } from '@/services/QueuePlaybackService'
import Component from './AlbumOrArtistThumbnail.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders for album', () => {
      expect(this.renderForAlbum().html()).toMatchSnapshot()
    })

    it('renders for artist', () => {
      expect(this.renderForArtist().html()).toMatchSnapshot()
    })

    it('plays album', async () => {
      this.createAudioPlayer()

      const songs = factory('song', 10)
      const fetchMock = this.mock(playableStore, 'fetchSongsForAlbum').mockResolvedValue(songs)
      const playMock = this.mock(playbackService, 'queueAndPlay')
      const { album } = this.renderForAlbum()

      await this.user.click(screen.getByRole('button'))

      await waitFor(() => {
        expect(fetchMock).toHaveBeenCalledWith(album)
        expect(playMock).toHaveBeenCalledWith(songs)
      })
    })

    it('queues album', async () => {
      this.createAudioPlayer()

      const songs = factory('song', 10)
      const fetchMock = this.mock(playableStore, 'fetchSongsForAlbum').mockResolvedValue(songs)
      const queueMock = this.mock(queueStore, 'queue')
      const { album } = this.renderForAlbum()

      await this.user.keyboard('{Alt>}')
      await this.user.click(screen.getByRole('button'))
      await this.user.keyboard('{/Alt}')

      await waitFor(() => {
        expect(fetchMock).toHaveBeenCalledWith(album)
        expect(queueMock).toHaveBeenCalledWith(orderBy(songs, ['disc', 'track']))
      })
    })

    it('plays artist', async () => {
      const songs = factory('song', 10)
      const fetchMock = this.mock(playableStore, 'fetchSongsForArtist').mockResolvedValue(songs)
      const playMock = this.mock(playbackService, 'queueAndPlay')
      const { artist } = this.renderForArtist()

      await this.user.click(screen.getByRole('button'))

      await waitFor(() => {
        expect(fetchMock).toHaveBeenCalledWith(artist)
        expect(playMock).toHaveBeenCalledWith(songs)
      })
    })

    it('queues artist', async () => {
      const songs = factory('song', 10)
      const fetchMock = this.mock(playableStore, 'fetchSongsForArtist').mockResolvedValue(songs)
      const queueMock = this.mock(queueStore, 'queue')
      const { artist } = this.renderForArtist()

      await this.user.keyboard('{Alt>}')
      await this.user.click(screen.getByRole('button'))
      await this.user.keyboard('{/Alt}')

      await waitFor(() => {
        expect(fetchMock).toHaveBeenCalledWith(artist)
        expect(queueMock).toHaveBeenCalledWith(orderBy(songs, ['album_id', 'disc', 'track']))
      })
    })
  }

  private renderForAlbum () {
    const album = factory('album', {
      name: 'IV',
      cover: 'https://test/album.jpg',
    })

    const rendered = this.render(Component, {
      props: {
        entity: album,
      },
    })

    return {
      ...rendered,
      album,
    }
  }

  private renderForArtist () {
    const artist = factory('artist', {
      name: 'Led Zeppelin',
      image: 'https://test/blimp.jpg',
    })

    const rendered = this.render(Component, {
      props: {
        entity: artist,
      },
    })

    return {
      ...rendered,
      artist,
    }
  }
}
