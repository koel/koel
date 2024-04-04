import { screen } from '@testing-library/vue'
import { it } from 'vitest'
import { playlistFolderStore, playlistStore } from '@/stores'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import SidebarPlaylistsSection from './SidebarPlaylistsSection.vue'
import PlaylistSidebarItem from './PlaylistSidebarItem.vue'
import PlaylistFolderSidebarItem from './PlaylistFolderSidebarItem.vue'

new class extends UnitTestCase {
  private renderComponent () {
    this.render(SidebarPlaylistsSection, {
      global: {
        stubs: {
          PlaylistSidebarItem,
          PlaylistFolderSidebarItem
        }
      }
    })
  }

  protected test () {
    it('displays orphan playlists', () => {
      playlistStore.state.playlists = [
        factory.states('orphan')<Playlist>('playlist', { name: 'Foo Playlist' }),
        factory.states('orphan')<Playlist>('playlist', { name: 'Bar Playlist' }),
        factory.states('smart', 'orphan')<Playlist>('playlist', { name: 'Smart Playlist' })
      ]

      this.renderComponent()

      ;['Favorites', 'Recently Played', 'Foo Playlist', 'Bar Playlist', 'Smart Playlist'].forEach(text => {
        screen.getByText(text)
      })
    })

    it('displays playlist folders', () => {
      playlistFolderStore.state.folders = [
        factory<PlaylistFolder>('playlist-folder', { name: 'Foo Folder' }),
        factory<PlaylistFolder>('playlist-folder', { name: 'Bar Folder' })
      ]

      this.renderComponent()
      ;['Foo Folder', 'Bar Folder'].forEach(text => screen.getByText(text))
    })
  }
}
