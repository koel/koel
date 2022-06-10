import { fireEvent } from '@testing-library/vue'
import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import { queueStore, songStore } from '@/stores'
import { playbackService } from '@/services'
import UnitTestCase from '@/__tests__/UnitTestCase'
import AlbumTrackListItem from './AlbumTrackListItem.vue'

let song: Song

const track = {
  title: 'Fahrstuhl to Heaven',
  fmtLength: '00:42'
}

const album = factory<Album>('album', { id: 42 })

new class extends UnitTestCase {
  protected beforeEach () {
    super.beforeEach(() => (song = factory<Song>('song')))
  }

  protected test () {
    it('renders', () => {
      const { html } = this.render(AlbumTrackListItem, {
        props: {
          album,
          track
        }
      })

      expect(html()).toMatchSnapshot()
    })

    it('plays', async () => {
      const guessMock = this.mock(songStore, 'match', song)
      const queueMock = this.mock(queueStore, 'queueIfNotQueued')
      const playMock = this.mock(playbackService, 'play')

      const { getByTitle } = this.render(AlbumTrackListItem, {
        props: {
          album,
          track
        }
      })

      await fireEvent.click(getByTitle('Click to play'))

      expect(guessMock).toHaveBeenCalledWith('Fahrstuhl to Heaven', album)
      expect(queueMock).toHaveBeenNthCalledWith(1, song)
      expect(playMock).toHaveBeenNthCalledWith(1, song)
    })
  }
}
