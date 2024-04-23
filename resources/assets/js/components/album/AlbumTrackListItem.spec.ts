import { screen } from '@testing-library/vue'
import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import { queueStore, songStore } from '@/stores'
import { playbackService } from '@/services'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { SongsKey } from '@/symbols'
import { ref } from 'vue'
import AlbumTrackListItem from './AlbumTrackListItem.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders', () => expect(this.renderComponent().html()).toMatchSnapshot())

    it('plays', async () => {
      const matchedSong = factory<Song>('song')
      const queueMock = this.mock(queueStore, 'queueIfNotQueued')
      const playMock = this.mock(playbackService, 'play')

      this.renderComponent(matchedSong)

      await this.user.click(screen.getByTitle('Click to play'))

      expect(queueMock).toHaveBeenNthCalledWith(1, matchedSong)
      expect(playMock).toHaveBeenNthCalledWith(1, matchedSong)
    })
  }

  private renderComponent (matchedSong?: Song) {
    const songsToMatchAgainst = factory<Song>('song', 10)
    const album = factory<Album>('album')

    const track = factory<AlbumTrack>('album-track', {
      title: 'Fahrstuhl to Heaven',
      length: 280
    })

    const matchMock = this.mock(songStore, 'match', matchedSong)

    const rendered = this.render(AlbumTrackListItem, {
      props: {
        album,
        track
      },
      global: {
        provide: {
          [<symbol>SongsKey]: ref(songsToMatchAgainst)
        }
      }
    })

    expect(matchMock).toHaveBeenCalledWith('Fahrstuhl to Heaven', songsToMatchAgainst)

    return rendered
  }
}
