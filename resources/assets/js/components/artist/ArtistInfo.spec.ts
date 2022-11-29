import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { commonStore, songStore } from '@/stores'
import { screen } from '@testing-library/vue'
import { mediaInfoService, playbackService } from '@/services'
import ArtistInfoComponent from './ArtistInfo.vue'

let artist: Artist

new class extends UnitTestCase {
  private async renderComponent (mode: MediaInfoDisplayMode = 'aside', info?: ArtistInfo) {
    commonStore.state.use_last_fm = true
    info ??= factory<ArtistInfo>('artist-info')
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
      await this.renderComponent(mode)

      if (mode === 'aside') {
        screen.getByTestId('thumbnail')
      } else {
        expect(screen.queryByTestId('thumbnail')).toBeNull()
      }

      expect(screen.getByTestId('artist-info').classList.contains(mode)).toBe(true)
    })

    it('triggers showing full bio for aside mode', async () => {
      await this.renderComponent('aside')
      expect(screen.queryByTestId('full')).toBeNull()

      await this.user.click(screen.getByRole('button', { name: 'Full Bio' }))

      expect(screen.queryByTestId('summary')).toBeNull()
      screen.getByTestId('full')
    })

    it('shows full bio for full mode', async () => {
      await this.renderComponent('full')

      screen.getByTestId('full')
      expect(screen.queryByTestId('summary')).toBeNull()
      expect(screen.queryByRole('button', { name: 'Full Bio' })).toBeNull()
    })

    it('plays', async () => {
      const songs = factory<Song>('song', 3)
      const fetchMock = this.mock(songStore, 'fetchForArtist').mockResolvedValue(songs)
      const playMock = this.mock(playbackService, 'queueAndPlay')
      await this.renderComponent()

      await this.user.click(screen.getByTitle('Play all songs by Led Zeppelin'))
      await this.tick(2)

      expect(fetchMock).toHaveBeenCalledWith(artist)
      expect(playMock).toHaveBeenCalledWith(songs)
    })
  }
}
