import factory from '@/__tests__/factory'
import { expect, it } from 'vitest'
import { fireEvent } from '@testing-library/vue'
import UnitTestCase from '@/__tests__/UnitTestCase'
import PlaylistSidebarItem from '@/components/playlist/PlaylistSidebarItem.vue'

new class extends UnitTestCase {
  renderComponent (playlist: Record<string, any>, type: PlaylistType = 'playlist') {
    return this.render(PlaylistSidebarItem, {
      props: {
        playlist,
        type
      },
      global: {
        stubs: {
          NameEditor: this.stub('name-editor')
        }
      }
    })
  }

  protected test () {
    it('edits the name of a standard playlist', async () => {
      const { getByTestId, queryByTestId } = this.renderComponent(factory<Playlist>('playlist', {
        id: 99,
        name: 'A Standard Playlist'
      }))

      expect(await queryByTestId('name-editor')).toBeNull()

      await fireEvent.dblClick(getByTestId('playlist-sidebar-item'))

      getByTestId('name-editor')
    })

    it('does not allow editing the name of the "Favorites" playlist', async () => {
      const { getByTestId, queryByTestId } = this.renderComponent({
        name: 'Favorites',
        songs: []
      }, 'favorites')

      expect(await queryByTestId('name-editor')).toBeNull()

      await fireEvent.dblClick(getByTestId('playlist-sidebar-item'))

      expect(await queryByTestId('name-editor')).toBeNull()
    })

    it('does not allow editing the name of the "Recently Played" playlist', async () => {
      const { getByTestId, queryByTestId } = this.renderComponent({
        name: 'Recently Played',
        songs: []
      }, 'recently-played')

      expect(await queryByTestId('name-editor')).toBeNull()

      await fireEvent.dblClick(getByTestId('playlist-sidebar-item'))

      expect(await queryByTestId('name-editor')).toBeNull()
    })
  }
}
