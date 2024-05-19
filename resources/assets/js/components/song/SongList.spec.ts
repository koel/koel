import { ref } from 'vue'
import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { arrayify } from '@/utils'
import {
  SelectedPlayablesKey,
  PlayableListConfigKey,
  PlayableListContextKey,
  PlayableListSortFieldKey,
  SongListSortOrderKey,
  PlayablesKey
} from '@/symbols'
import { screen } from '@testing-library/vue'
import SongList from './SongList.vue'

let songs: Playable[]

new class extends UnitTestCase {
  protected test () {
    it('renders', async () => {
      const { html } = await this.renderComponent(factory<Song>('song', 5))
      expect(html()).toMatchSnapshot()
    })

    it.each<[PlayableListSortField, string]>([
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

  private async renderComponent (
    _songs: MaybeArray<Playable>,
    config: Partial<PlayableListConfig> = {
      sortable: true,
      reorderable: true
    },
    context: PlayableListContext = {
      type: 'Album',
    },
    selectedPlayables: Playable[] = [],
    sortField: PlayableListSortField = 'title',
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
          [<symbol>PlayablesKey]: [ref(songs)],
          [<symbol>SelectedPlayablesKey]: [ref(selectedPlayables), (value: Song[]) => (selectedPlayables = value)],
          [<symbol>PlayableListConfigKey]: [config],
          [<symbol>PlayableListContextKey]: [context],
          [<symbol>PlayableListSortFieldKey]: [sortFieldRef, (value: PlayableListSortField) => (sortFieldRef.value = value)],
          [<symbol>SongListSortOrderKey]: [sortOrderRef, (value: SortOrder) => (sortOrderRef.value = value)]
        }
      }
    })
  }
}
