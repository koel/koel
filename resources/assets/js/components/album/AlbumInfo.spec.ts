import { screen } from '@testing-library/vue'
import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { commonStore } from '@/stores'
import { mediaInfoService } from '@/services'
import AlbumInfoComponent from './AlbumInfo.vue'

let album: Album

new class extends UnitTestCase {
  private async renderComponent (mode: MediaInfoDisplayMode = 'aside', info?: AlbumInfo) {
    commonStore.state.uses_last_fm = true

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
  }
}
