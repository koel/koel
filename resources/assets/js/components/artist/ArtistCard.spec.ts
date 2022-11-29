import { screen } from '@testing-library/vue'
import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import { downloadService, playbackService } from '@/services'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { commonStore, songStore } from '@/stores'
import ArtistCard from './ArtistCard.vue'

let artist: Artist

new class extends UnitTestCase {
  protected beforeEach () {
    super.beforeEach(() => {
      artist = factory<Artist>('artist', {
        id: 42,
        name: 'Led Zeppelin'
      })
    })
  }

  private renderComponent () {
    return this.render(ArtistCard, {
      props: {
        artist
      },
      global: {
        stubs: {
          AlbumArtistThumbnail: this.stub('thumbnail')
        }
      }
    })
  }

  protected test () {
    it('renders', () => expect(this.renderComponent().html()).toMatchSnapshot())

    it('downloads', async () => {
      const mock = this.mock(downloadService, 'fromArtist')
      this.renderComponent()

      await this.user.click(screen.getByTitle('Download all songs by Led Zeppelin'))
      expect(mock).toHaveBeenCalledOnce()
    })

    it('does not have an option to download if downloading is disabled', async () => {
      commonStore.state.allow_download = false
      this.renderComponent()

      expect(screen.queryByText('Download')).toBeNull()
    })

    it('shuffles', async () => {
      const songs = factory<Song>('song', 16)
      const fetchMock = this.mock(songStore, 'fetchForArtist').mockResolvedValue(songs)
      const playMock = this.mock(playbackService, 'queueAndPlay')

      this.renderComponent()

      await this.user.click(screen.getByTitle('Shuffle all songs by Led Zeppelin'))
      await this.tick()

      expect(fetchMock).toHaveBeenCalledWith(artist)
      expect(playMock).toHaveBeenCalledWith(songs, true)
    })
  }
}
