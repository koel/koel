import { screen } from '@testing-library/vue'
import { ref } from 'vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import {
  PlayableListConfigKey,
  PlayableListContextKey,
  PlayableListSortFieldKey,
  PlayableListSortOrderKey,
  SelectedPlayablesKey,
} from '@/config/symbols'
import PlayableListHeader from './PlayableListHeader.vue'

describe('playableListHeader.vue', () => {
  const h = createHarness()

  const renderComponent = async (
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
    const sortFieldRef = ref(sortField)
    const sortOrderRef = ref(sortOrder)

    h.visit('/songs')

    return h.render(PlayableListHeader, {
      props: {
        contentType: 'songs',
      },
      global: {
        stubs: {
          ActionMenu: h.stub(),
        },
        provide: {
          [<symbol>SelectedPlayablesKey]: [ref(selectedPlayables), (value: Playable[]) => (selectedPlayables = value)],
          [<symbol>PlayableListConfigKey]: [config],
          [<symbol>PlayableListContextKey]: [context],
          [<symbol>PlayableListSortFieldKey]: [
            sortFieldRef,
            (value: PlayableListSortField) => (sortFieldRef.value = value),
          ],
          [<symbol>PlayableListSortOrderKey]: [sortOrderRef, (value: SortOrder) => (sortOrderRef.value = value)],
        },
      },
    })
  }

  it('renders', async () => {
    const { html } = await renderComponent()
    expect(html()).toMatchSnapshot()
  })

  it.each<[PlayableListSortField, string]>([
    ['track', 'header-track-number'],
    ['title', 'header-title'],
    ['album_name', 'header-album'],
    ['length', 'header-length'],
  ])('sorts by %s upon %s clicked', async (field, testId) => {
    const { emitted } = await renderComponent()

    await h.user.click(screen.getByTestId(testId))
    expect(emitted().sort[0]).toEqual([field, 'desc'])

    await h.user.click(screen.getByTestId(testId))
    expect(emitted().sort[1]).toEqual([field, 'asc'])
  })

  it('shows collaborative columns when collaborative', async () => {
    await renderComponent({
      sortable: true,
      reorderable: true,
      collaborative: true,
    })

    screen.getByTestId('header-collaborator')
    screen.getByTestId('header-contributed-at')
  })

  it('does not show collaborative columns when not collaborative', async () => {
    await renderComponent()

    expect(screen.queryByTestId('header-collaborator')).toBeNull()
    expect(screen.queryByTestId('header-contributed-at')).toBeNull()
  })

  it.each<[PlayableListSortField, string]>([
    ['collaboration.user.name', 'header-collaborator'],
    ['collaboration.added_at', 'header-contributed-at'],
  ])('sorts collaborative column by %s upon %s clicked', async (field, testId) => {
    const { emitted } = await renderComponent({
      sortable: true,
      reorderable: true,
      collaborative: true,
    })

    await h.user.click(screen.getByTestId(testId))
    expect(emitted().sort[0]).toEqual([field, 'desc'])

    await h.user.click(screen.getByTestId(testId))
    expect(emitted().sort[1]).toEqual([field, 'asc'])
  })

  it('cannot be sorted if configured so', async () => {
    const { emitted } = await renderComponent({
      sortable: false,
      reorderable: true,
    })

    await h.user.click(screen.getByTestId('header-track-number'))
    expect(emitted().sort).toBeUndefined()
  })
})
