import { describe, it } from 'vitest'
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
      factory.states('orphan')('playlist', { name: 'Foo Playlist' }),
      factory.states('orphan')('playlist', { name: 'Bar Playlist' }),
      factory.states('smart', 'orphan')('playlist', { name: 'Smart Playlist' }),
    ]

    renderComponent()

    ;['Favorites', 'Recently Played', 'Foo Playlist', 'Bar Playlist', 'Smart Playlist'].forEach(text => {
      screen.getByText(text)
    })
  })

  it('displays playlist folders', () => {
    playlistFolderStore.state.folders = [
      h.factory('playlist-folder', { name: 'Foo Folder' }),
      h.factory('playlist-folder', { name: 'Bar Folder' }),
    ]

    renderComponent()
    ;['Foo Folder', 'Bar Folder'].forEach(text => screen.getByText(text))
  })
})
