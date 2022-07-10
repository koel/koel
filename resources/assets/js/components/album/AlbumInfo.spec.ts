import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { mediaInfoService } from '@/services/mediaInfoService'
import { commonStore, songStore } from '@/stores'
import { fireEvent } from '@testing-library/vue'
import { playbackService } from '@/services'
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
          'TrackList': this.stub()
        }
      }
    })

    await this.tick(1)
    expect(fetchMock).toHaveBeenCalledWith(album)

    return rendered
  }

  protected test () {
    it.each<[MediaInfoDisplayMode]>([['aside'], ['full']])('renders in %s mode', async (mode) => {
      const { getByTestId } = await this.renderComponent(mode)

      getByTestId('album-artist-thumbnail')
      getByTestId('album-info-tracks')

      expect(getByTestId('album-info').classList.contains(mode)).toBe(true)
    })

    it('triggers showing full wiki for aside mode', async () => {
      const { queryByTestId } = await this.renderComponent('aside')
      expect(queryByTestId('full')).toBeNull()

      await fireEvent.click(queryByTestId('more-btn'))

      expect(queryByTestId('summary')).toBeNull()
      expect(queryByTestId('full')).not.toBeNull()
    })

    it('shows full wiki for full mode', async () => {
      const { queryByTestId } = await this.renderComponent('full')

      expect(queryByTestId('full')).not.toBeNull()
      expect(queryByTestId('summary')).toBeNull()
      expect(queryByTestId('more-btn')).toBeNull()
    })

    it('plays', async () => {
      const songs = factory<Song[]>('song', 3)
      const fetchMock = this.mock(songStore, 'fetchForAlbum').mockResolvedValue(songs)
      const playMock = this.mock(playbackService, 'queueAndPlay')
      const { getByTitle } = await this.renderComponent()

      await fireEvent.click(getByTitle('Play all songs in IV'))
      await this.tick(2)

      expect(fetchMock).toHaveBeenCalledWith(album)
      expect(playMock).toHaveBeenCalledWith(songs)
    })
  }
}
