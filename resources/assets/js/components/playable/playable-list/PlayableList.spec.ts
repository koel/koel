import { ref } from 'vue'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
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

describe('playableList.vue', () => {
  const h = createHarness()

  const renderComponent = async (
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
  ) => {
    songs = arrayify(songs)

    const sortFieldRef = ref(sortField)
    const sortOrderRef = ref(sortOrder)

    const rendered = h.visit('/songs').render(Component, {
      global: {
        stubs: {
          VirtualScroller: h.stub(),
          PlayableListSorter: h.stub(),
          PlayableListHeader: h.stub(),
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

  it('renders', async () => {
    const { html } = await renderComponent(h.factory('song', 5))
    expect(html()).toMatchSnapshot()
  })
})
