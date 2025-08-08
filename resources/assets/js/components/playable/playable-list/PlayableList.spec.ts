import { ref } from 'vue'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { arrayify } from '@/utils/helpers'
import {
  FilteredPlayablesKey,
  PlayableListConfigKey,
  PlayableListContextKey,
  PlayableListSortFieldKey,
  PlayableListSortOrderKey,
  SelectedPlayablesKey,
} from '@/symbols'
import Component from './PlayableList.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders', async () => {
      const { html } = await this.renderComponent(factory('song', 5))
      expect(html()).toMatchSnapshot()
    })
  }

  private async renderComponent (
    songs: MaybeArray<Playable>,
    config: Partial<PlayableListConfig> = {
      sortable: true,
      reorderable: true,
    },
    context: PlayableListContext = {
      type: 'Album',
    },
    selectedPlayables: Playable[] = [],
    sortField: PlayableListSortField = 'title',
    sortOrder: SortOrder = 'asc',
  ) {
    songs = arrayify(songs)

    const sortFieldRef = ref(sortField)
    const sortOrderRef = ref(sortOrder)

    await this.router.activateRoute({
      screen: 'Songs',
      path: '/songs',
    })

    const rendered = this.render(Component, {
      global: {
        stubs: {
          VirtualScroller: this.stub(),
          PlayableListSorter: this.stub(),
          PlayableListHeader: this.stub(),
        },
        provide: {
          [<symbol>FilteredPlayablesKey]: [ref(songs)],
          [<symbol>SelectedPlayablesKey]: [ref(selectedPlayables), (value: Playable[]) => (selectedPlayables = value)],
          [<symbol>PlayableListConfigKey]: [config],
          [<symbol>PlayableListContextKey]: [context],
          [<symbol>PlayableListSortFieldKey]: [sortFieldRef, (value: PlayableListSortField) => (sortFieldRef.value = value)],
          [<symbol>PlayableListSortOrderKey]: [sortOrderRef, (value: SortOrder) => (sortOrderRef.value = value)],
        },
      },
    })

    return {
      ...rendered,
      songs,
    }
  }
}
