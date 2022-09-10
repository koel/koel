import { ref } from 'vue'
import { expect, it } from 'vitest'
import { fireEvent } from '@testing-library/vue'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { arrayify } from '@/utils'
import {
  SelectedSongsKey,
  SongListConfigKey,
  SongListSortFieldKey,
  SongListSortOrderKey,
  SongListTypeKey,
  SongsKey
} from '@/symbols'
import SongList from './SongList.vue'

let songs: Song[]

new class extends UnitTestCase {
  private renderComponent (
    _songs: Song | Song[],
    type: SongListType = 'all-songs',
    config: Partial<SongListConfig> = {},
    selectedSongs: Song[] = [],
    sortField: SongListSortField = 'title',
    sortOrder: SortOrder = 'asc'
  ) {
    songs = arrayify(_songs)

    const sortFieldRef = ref(sortField)
    const sortOrderRef = ref(sortOrder)

    return this.render(SongList, {
      global: {
        stubs: {
          VirtualScroller: this.stub('virtual-scroller')
        },
        provide: {
          [<symbol>SongsKey]: [ref(songs)],
          [<symbol>SelectedSongsKey]: [ref(selectedSongs), value => (selectedSongs = value)],
          [<symbol>SongListTypeKey]: [ref(type)],
          [<symbol>SongListConfigKey]: [config],
          [<symbol>SongListSortFieldKey]: [sortFieldRef, value => (sortFieldRef.value = value)],
          [<symbol>SongListSortOrderKey]: [sortOrderRef, value => (sortOrderRef.value = value)]
        }
      }
    })
  }

  protected test () {
    it('renders', async () => {
      const { html } = this.renderComponent(factory<Song>('song', 5))
      expect(html()).toMatchSnapshot()
    })

    it.each<[SongListSortField, string]>([
      ['track', 'header-track-number'],
      ['title', 'header-title'],
      ['album_name', 'header-album'],
      ['length', 'header-length'],
      ['artist_name', 'header-artist']
    ])('sorts by %s upon %s clicked', async (field, testId) => {
      const { getByTestId, emitted } = this.renderComponent(factory<Song>('song', 5))

      await fireEvent.click(getByTestId(testId))
      expect(emitted().sort[0]).toEqual([field, 'desc'])

      await fireEvent.click(getByTestId(testId))
      expect(emitted().sort[1]).toEqual([field, 'asc'])
    })
  }
}
