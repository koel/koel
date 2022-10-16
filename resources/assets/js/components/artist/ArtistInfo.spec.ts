import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { commonStore, songStore } from '@/stores'
import { fireEvent } from '@testing-library/vue'
import { mediaInfoService, playbackService } from '@/services'
import ArtistInfoComponent from './ArtistInfo.vue'

let artist: Artist

new class extends UnitTestCase {
  private async renderComponent (mode: MediaInfoDisplayMode = 'aside', info?: ArtistInfo) {
    commonStore.state.use_last_fm = true

    if (info === undefined) {
      info = factory<ArtistInfo>('artist-info')
    }

    artist = factory<Artist>('artist', { name: 'Led Zeppelin' })
    const fetchMock = this.mock(mediaInfoService, 'fetchForArtist').mockResolvedValue(info)

    const rendered = this.render(ArtistInfoComponent, {
      props: {
        artist,
        mode
      },
      global: {
        stubs: {
          ArtistThumbnail: this.stub('thumbnail')
        }
      }
    })

    await this.tick(1)
    expect(fetchMock).toHaveBeenCalledWith(artist)

    return rendered
  }

  protected test () {
    it.each<[MediaInfoDisplayMode]>([['aside'], ['full']])('renders in %s mode', async (mode) => {
      const { getByTestId, queryByTestId } = await this.renderComponent(mode)

      if (mode === 'aside') {
        getByTestId('thumbnail')
      } else {
        expect(queryByTestId('thumbnail'))
      }

      expect(getByTestId('artist-info').classList.contains(mode)).toBe(true)
    })

    it('triggers showing full bio for aside mode', async () => {
      const { queryByTestId, getByTestId } = await this.renderComponent('aside')
      expect(queryByTestId('full')).toBeNull()

      await fireEvent.click(getByTestId('more-btn'))

      expect(queryByTestId('summary')).toBeNull()
      expect(queryByTestId('full')).not.toBeNull()
    })

    it('shows full bio for full mode', async () => {
      const { queryByTestId } = await this.renderComponent('full')

      expect(queryByTestId('full')).not.toBeNull()
      expect(queryByTestId('summary')).toBeNull()
      expect(queryByTestId('more-btn')).toBeNull()
    })

    it('plays', async () => {
      const songs = factory<Song>('song', 3)
      const fetchMock = this.mock(songStore, 'fetchForArtist').mockResolvedValue(songs)
      const playMock = this.mock(playbackService, 'queueAndPlay')
      const { getByTitle } = await this.renderComponent()

      await fireEvent.click(getByTitle('Play all songs by Led Zeppelin'))
      await this.tick(2)

      expect(fetchMock).toHaveBeenCalledWith(artist)
      expect(playMock).toHaveBeenCalledWith(songs)
    })
  }
}
