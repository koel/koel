import { describe, it } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import factory from '@/__tests__/factory'
import { playlistFolderStore } from '@/stores/playlistFolderStore'
import { playlistStore } from '@/stores/playlistStore'
import PlaylistSidebarItem from './PlaylistSidebarItem.vue'
import PlaylistFolderSidebarItem from './PlaylistFolderSidebarItem.vue'
import Component from './SidebarPlaylistsSection.vue'

describe('sidebarPlaylistsSection.vue', () => {
  const h = createHarness()

  const renderComponent = () => {
    h.render(Component, {
      global: {
        stubs: {
          PlaylistSidebarItem,
          PlaylistFolderSidebarItem,
        },
      },
    })
  }

  it('displays orphan playlists', () => {
    playlistStore.state.playlists = [
      factory('playlist').state('orphan').make({ name: 'Foo Playlist' }),
      factory('playlist').state('orphan').make({ name: 'Bar Playlist' }),
      factory('playlist').state('smart', 'orphan').make({ name: 'Smart Playlist' }),
    ]

    renderComponent()

    ;['Favorites', 'Recently Played', 'Foo Playlist', 'Bar Playlist', 'Smart Playlist'].forEach(text => {
      screen.getByText(text)
    })
  })

  it('displays playlist folders', () => {
    playlistFolderStore.state.folders = [
      h.factory('playlist-folder').make({ name: 'Foo Folder' }),
      h.factory('playlist-folder').make({ name: 'Bar Folder' }),
    ]

    renderComponent()
    ;['Foo Folder', 'Bar Folder'].forEach(text => screen.getByText(text))
  })
})
