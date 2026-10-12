import { describe, expect, it } from 'vite-plus/test'
import { fireEvent, screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import factory from '@/__tests__/factory'
import { playlistFolderStore } from '@/stores/playlistFolderStore'
import { playlistStore } from '@/stores/playlistStore'
import PlaylistSidebarItem from './PlaylistSidebarItem.vue'
import Component from './SidebarPlaylistsSection.vue'

describe('sidebarPlaylistsSection.vue', () => {
  const h = createHarness()

  const renderComponent = () => {
    h.render(Component, {
      global: {
        stubs: {
          PlaylistSidebarItem,
          PlaylistFolderSidebarItem: {
            props: ['folder'],
            template: '<li>{{ folder.name }}</li>',
          },
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

  it('displays only root playlist folders', () => {
    const firstRoot = h.factory('playlist-folder').make({ name: 'First Root Folder', parent_id: null })
    const secondRoot = h.factory('playlist-folder').make({ name: 'Second Root Folder', parent_id: null })
    const child = h.factory('playlist-folder').make({ name: 'Child Folder', parent_id: firstRoot.id })
    playlistFolderStore.state.folders = [firstRoot, child, secondRoot]

    renderComponent()

    screen.getByText(firstRoot.name)
    screen.getByText(secondRoot.name)
    expect(screen.queryByText(child.name)).toBeNull()
  })

  it('moves a dropped folder to root', async () => {
    const parent = h.factory('playlist-folder').make({ parent_id: null })
    const child = h.factory('playlist-folder').make({ parent_id: parent.id })
    playlistFolderStore.state.folders = [parent, child]
    const moveMock = h.mock(playlistFolderStore, 'moveFolderToFolder')

    renderComponent()

    const rootList = screen.getByRole('list')
    const dataTransfer = {
      dropEffect: '',
      types: ['application/x-koel.playlist-folder'],
      getData: () => JSON.stringify(child.id),
    }
    let wasDragOverPrevented = false
    rootList.addEventListener('dragover', event => {
      wasDragOverPrevented = event.defaultPrevented
    })

    await fireEvent.dragOver(rootList, { dataTransfer })
    expect(wasDragOverPrevented).toBe(true)
    await fireEvent.drop(rootList, {
      dataTransfer: {
        types: ['application/x-koel.playlist-folder'],
        getData: () => JSON.stringify(child.id),
      },
    })

    expect(moveMock).toHaveBeenCalledWith(child, null)
  })
})
