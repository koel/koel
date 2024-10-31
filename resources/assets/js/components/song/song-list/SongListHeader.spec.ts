import { screen } from '@testing-library/vue'
import { ref } from 'vue'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import {
  PlayableListConfigKey,
  PlayableListContextKey,
  PlayableListSortFieldKey,
  SelectedPlayablesKey,
  SongListSortOrderKey,
} from '@/symbols'
import Component from './SongListHeader.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders', async () => {
      const { html } = await this.renderComponent()
      expect(html()).toMatchSnapshot()
    })

    it.each<[PlayableListSortField, string]>([
      ['track', 'header-track-number'],
      ['title', 'header-title'],
      ['album_name', 'header-album'],
      ['length', 'header-length'],
    ])('sorts by %s upon %s clicked', async (field, testId) => {
      const { emitted } = await this.renderComponent()

      await this.user.click(screen.getByTestId(testId))
      expect(emitted().sort[0]).toEqual([field, 'desc'])

      await this.user.click(screen.getByTestId(testId))
      expect(emitted().sort[1]).toEqual([field, 'asc'])
    })

    it('cannot be sorted if configured so', async () => {
      const { emitted } = await this.renderComponent({
        sortable: false,
        reorderable: true,
      })

      await this.user.click(screen.getByTestId('header-track-number'))
      expect(emitted().sort).toBeUndefined()
    })
  }

  private async renderComponent (
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
    const sortFieldRef = ref(sortField)
    const sortOrderRef = ref(sortOrder)

    await this.router.activateRoute({
      screen: 'Songs',
      path: '/songs',
    })

    return this.render(Component, {
      props: {
        contentType: 'songs',
      },
      global: {
        stubs: {
          ActionMenu: this.stub('song-list-header-action-menu'),
        },
        provide: {
          [<symbol>SelectedPlayablesKey]: [ref(selectedPlayables), (value: Playable[]) => (selectedPlayables = value)],
          [<symbol>PlayableListConfigKey]: [config],
          [<symbol>PlayableListContextKey]: [context],
          [<symbol>PlayableListSortFieldKey]: [sortFieldRef, (value: PlayableListSortField) => (sortFieldRef.value = value)],
          [<symbol>SongListSortOrderKey]: [sortOrderRef, (value: SortOrder) => (sortOrderRef.value = value)],
        },
      },
    })
  }
}
