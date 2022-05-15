import lodash from 'lodash'
import factory from '@/__tests__/factory'
import { expect, it } from 'vitest'
import { fireEvent } from '@testing-library/vue'
import { eventBus, noop } from '@/utils'
import UnitTestCase from '@/__tests__/UnitTestCase'
import SongList from './SongList.vue'

let songs: Song[]

new class extends UnitTestCase {
  protected beforeEach () {
    // suppress the warning
    super.beforeEach(() => eventBus.on('SET_SELECTED_SONGS', noop))
  }

  private renderComponent (type: SongListType = 'all-songs') {
    songs = factory<Song>('song', 3)

    return this.render(SongList, {
      props: {
        items: songs,
        type
      }
    })
  }

  protected test () {
    it.each<[string, SongListSortField[]]>([
      ['header-track-number', ['song.track', 'song.disc']],
      ['header-title', ['song.title']],
      ['header-artist', ['song.album.artist.name', 'song.album.name', 'song.track', 'song.disc']],
      ['header-album', ['song.album.name', 'song.track', 'song.disc']],
      ['header-length', ['song.length']]
    ])('sorts when "%s" header is clicked', async (testId: string, sortFields: SongListSortField[]) => {
      const mock = this.mock(lodash, 'orderBy', [])
      const { getByTestId } = this.renderComponent()

      await fireEvent.click(getByTestId(testId))

      expect(mock).toHaveBeenCalledWith(expect.anything(), sortFields, 'asc')
    })

    it.each<[string, string]>([
      ['Enter', 'press:enter'],
      ['Delete', 'press:delete']
    ])('emits when %s key is pressed', async (key: string, eventName: string) => {
      const { emitted, getByTestId } = this.renderComponent()

      await fireEvent.keyDown(getByTestId('song-list'), { key })

      expect(emitted()[eventName]).toBeTruthy()
    })
  }
}
