import { ref } from 'vue'
import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { arrayify } from '@/utils'
import { SelectedSongsKey, SongListConfigKey, SongListSortFieldKey, SongListSortOrderKey, SongsKey } from '@/symbols'
import { screen } from '@testing-library/vue'
import SongList from './SongList.vue'

let songs: Song[]

new class extends UnitTestCase {
  private async renderComponent (
    _songs: Song | Song[],
    config: Partial<SongListConfig> = {
      sortable: true,
      reorderable: true
    },
    selectedSongs: Song[] = [],
    sortField: SongListSortField = 'title',
    sortOrder: SortOrder = 'asc'
  ) {
    songs = arrayify(_songs)

    const sortFieldRef = ref(sortField)
    const sortOrderRef = ref(sortOrder)

    await this.router.activateRoute({
      screen: 'Songs',
      path: '/songs'
    })

    return this.render(SongList, {
      global: {
        stubs: {
          VirtualScroller: this.stub('virtual-scroller'),
          SongListSorter: this.stub('song-list-sorter')
        },
        provide: {
          [<symbol>SongsKey]: [ref(songs)],
          [<symbol>SelectedSongsKey]: [ref(selectedSongs), value => (selectedSongs = value)],
          [<symbol>SongListConfigKey]: [config],
          [<symbol>SongListSortFieldKey]: [sortFieldRef, value => (sortFieldRef.value = value)],
          [<symbol>SongListSortOrderKey]: [sortOrderRef, value => (sortOrderRef.value = value)]
        }
      }
    })
  }

  protected test () {
    it('renders', async () => {
      const { html } = await this.renderComponent(factory<Song>('song', 5))
      expect(html()).toMatchSnapshot()
    })

    it.each<[SongListSortField, string]>([
      ['track', 'header-track-number'],
      ['title', 'header-title'],
      ['album_name', 'header-album'],
      ['length', 'header-length']
    ])('sorts by %s upon %s clicked', async (field, testId) => {
      const { emitted } = await this.renderComponent(factory<Song>('song', 5))

      await this.user.click(screen.getByTestId(testId))
      expect(emitted().sort[0]).toEqual([field, 'desc'])

      await this.user.click(screen.getByTestId(testId))
      expect(emitted().sort[1]).toEqual([field, 'asc'])
    })

    it('cannot be sorted if configured so', async () => {
      const { emitted } = await this.renderComponent(factory<Song>('song', 5), {
        sortable: false,
        reorderable: true
      })

      await this.user.click(screen.getByTestId('header-track-number'))
      expect(emitted().sort).toBeUndefined()
    })
  }
}
