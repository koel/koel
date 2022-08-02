import { expect, it } from 'vitest'
import { ref } from 'vue'
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
import { fireEvent } from '@testing-library/vue'

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

    return this.render(SongList, {
      global: {
        stubs: {
          VirtualScroller: this.stub('virtual-scroller')
        },
        provide: {
          [SongsKey]: [ref(songs)],
          [SelectedSongsKey]: [ref(selectedSongs), value => selectedSongs = value],
          [SongListTypeKey]: [ref(type)],
          [SongListConfigKey]: [config],
          [SongListSortFieldKey]: [ref(sortField), value => sortField = value],
          [SongListSortOrderKey]: [ref(sortOrder), value => sortOrder = value]
        }
      }
    })
  }

  protected test () {
    it('renders', async () => {
      const { html } = this.renderComponent(factory<Song[]>('song', 5))
      expect(html()).toMatchSnapshot()
    })

    it.each([
      ['track', 'header-track-number'],
      ['title', 'header-title'],
      ['album_name', 'header-album'],
      ['length', 'header-length'],
      ['artist_name', 'header-artist']
    ])('sorts by %s upon %s clicked', async (field: SongListSortField, testId: string) => {
      const { getByTestId, emitted } = this.renderComponent(factory<Song[]>('song', 5))

      await fireEvent.click(getByTestId(testId))
      expect(emitted().sort[0]).toBeTruthy([field, 'asc'])

      await fireEvent.click(getByTestId(testId))
      expect(emitted().sort[0]).toBeTruthy([field, 'desc'])
    })
  }
}
