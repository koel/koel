import { screen } from '@testing-library/vue'
import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { commonStore, songStore } from '@/stores'
import { mediaInfoService, playbackService } from '@/services'
import AlbumInfoComponent from './AlbumInfo.vue'

let album: Album

new class extends UnitTestCase {
  private async renderComponent (mode: MediaInfoDisplayMode = 'aside', info?: AlbumInfo) {
    commonStore.state.use_last_fm = true

    if (info === undefined) {
      info = factory<AlbumInfo>('album-info')
    }

    album = factory<Album>('album', { name: 'IV' })
    const fetchMock = this.mock(mediaInfoService, 'fetchForAlbum').mockResolvedValue(info)

    const rendered = this.render(AlbumInfoComponent, {
      props: {
        album,
        mode
      },
      global: {
        stubs: {
          TrackList: this.stub(),
          AlbumThumbnail: this.stub('thumbnail')
        }
      }
    })

    await this.tick(1)
    expect(fetchMock).toHaveBeenCalledWith(album)

    return rendered
  }

  protected test () {
    it.each<[MediaInfoDisplayMode]>([['aside'], ['full']])('renders in %s mode', async (mode) => {
      await this.renderComponent(mode)

      screen.getByTestId('album-info-tracks')

      if (mode === 'aside') {
        screen.getByTestId('thumbnail')
      } else {
        expect(screen.queryByTestId('thumbnail')).toBeNull()
      }

      expect(screen.getByTestId('album-info').classList.contains(mode)).toBe(true)
    })

    it('triggers showing full wiki for aside mode', async () => {
      await this.renderComponent('aside')
      expect(screen.queryByTestId('full')).toBeNull()

      await this.user.click(screen.getByRole('button', { name: 'Full Wiki' }))

      expect(screen.queryByTestId('summary')).toBeNull()
      screen.getByTestId('full')
    })

    it('shows full wiki for full mode', async () => {
      await this.renderComponent('full')

      screen.getByTestId('full')
      expect(screen.queryByTestId('summary')).toBeNull()
      expect(screen.queryByRole('button', { name: 'Full Wiki' })).toBeNull()
    })

    it('plays', async () => {
      const songs = factory<Song>('song', 3)
      const fetchMock = this.mock(songStore, 'fetchForAlbum').mockResolvedValue(songs)
      const playMock = this.mock(playbackService, 'queueAndPlay')
      await this.renderComponent()

      await this.user.click(screen.getByTitle('Play all songs in IV'))
      await this.tick(2)

      expect(fetchMock).toHaveBeenCalledWith(album)
      expect(playMock).toHaveBeenCalledWith(songs)
    })
  }
}
