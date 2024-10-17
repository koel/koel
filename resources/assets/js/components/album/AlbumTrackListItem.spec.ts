import { ref } from 'vue'
import { screen } from '@testing-library/vue'
import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { songStore } from '@/stores/songStore'
import { playbackService } from '@/services/playbackService'
import { PlayablesKey } from '@/symbols'
import AlbumTrackListItem from './AlbumTrackListItem.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders', () => expect(this.renderComponent().html()).toMatchSnapshot())

    it('plays', async () => {
      const matchedSong = factory('song')
      const playMock = this.mock(playbackService, 'play')

      this.renderComponent(matchedSong)

      await this.user.click(screen.getByTitle('Click to play'))

      expect(playMock).toHaveBeenCalledWith(matchedSong)
    })
  }

  private renderComponent (matchedSong?: Song) {
    const songsToMatchAgainst = factory('song', 10)
    const album = factory('album')

    const track = factory('album-track', {
      title: 'Fahrstuhl to Heaven',
      length: 280,
    })

    const matchMock = this.mock(songStore, 'match', matchedSong)

    const rendered = this.render(AlbumTrackListItem, {
      props: {
        album,
        track,
      },
      global: {
        provide: {
          [<symbol>PlayablesKey]: ref(songsToMatchAgainst),
        },
      },
    })

    expect(matchMock).toHaveBeenCalledWith('Fahrstuhl to Heaven', songsToMatchAgainst)

    return rendered
  }
}
