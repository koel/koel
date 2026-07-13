import type { Mock } from 'vite-plus/test'
import { describe, expect, it, vi } from 'vite-plus/test'
import { fireEvent, screen } from '@testing-library/vue'
import { ref } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import { assertOpenContextMenu } from '@/__tests__/assertions'
import { useContextMenu } from '@/composables/useContextMenu'
import { DraggedPlaylistFolderKey, PlaylistFolderDropTargetKey } from '@/config/symbols'
import { playlistFolderStore } from '@/stores/playlistFolderStore'
import { playlistStore } from '@/stores/playlistStore'
import PlaylistFolderContextMenu from '@/components/playlist/PlaylistFolderContextMenu.vue'
import PlaylistContextMenu from '@/components/playlist/PlaylistContextMenu.vue'
import Component from './PlaylistFolderSidebarItem.vue'

vi.mock('@/composables/useContextMenu')

describe('playlistFolderSidebarItem.vue', () => {
  const h = createHarness()

  it('renders folder name', () => {
    const folder = h.factory('playlist-folder').make()

    h.render(Component, {
      props: { folder },
    })

    screen.getByText(folder.name)
  })

  it('opens the context menu for the focused nested item without bubbling', async () => {
    const root = h.factory('playlist-folder').make({ name: 'Root Folder', parent_id: null })
    const child = h.factory('playlist-folder').make({ name: 'Child Folder', parent_id: root.id })
    const playlist = h.factory('playlist').make({ name: 'Playlist', folder_id: root.id })
    playlistFolderStore.state.folders = [root, child]
    playlistStore.state.playlists = [playlist]
    const { openContextMenu } = useContextMenu()

    h.render(Component, {
      props: { folder: root },
    })

    const rootRow = screen.getByText(root.name).closest<HTMLElement>('[tabindex="0"]')!
    rootRow.focus()
    await fireEvent.contextMenu(rootRow)

    expect(document.activeElement).toBe(rootRow)
    await assertOpenContextMenu(openContextMenu as Mock, PlaylistFolderContextMenu, { folder: root })

    await h.user.click(rootRow)
    ;(openContextMenu as Mock).mockClear()
    await fireEvent.contextMenu(screen.getByText(child.name).closest('[tabindex="0"]')!)

    expect(openContextMenu).toHaveBeenCalledOnce()
    await assertOpenContextMenu(openContextMenu as Mock, PlaylistFolderContextMenu, { folder: child })

    ;(openContextMenu as Mock).mockClear()
    await fireEvent.contextMenu(screen.getByText(playlist.name).closest('.playlist')!)

    expect(openContextMenu).toHaveBeenCalledOnce()
    await assertOpenContextMenu(openContextMenu as Mock, PlaylistContextMenu, { playlist })
  })

  it('reveals nested folders recursively', async () => {
    const root = h.factory('playlist-folder').make({ name: 'Root Folder', parent_id: null })
    const child = h.factory('playlist-folder').make({ name: 'Child Folder', parent_id: root.id })
    const grandchild = h.factory('playlist-folder').make({ name: 'Grandchild Folder', parent_id: child.id })
    playlistFolderStore.state.folders = [root, child, grandchild]

    h.render(Component, {
      props: { folder: root },
    })

    expect(screen.queryByText(child.name)).toBeNull()

    await h.user.click(screen.getByText(root.name))
    screen.getByText(child.name)
    expect(screen.queryByText(grandchild.name)).toBeNull()

    await h.user.click(screen.getByText(child.name))
    screen.getByText(grandchild.name)
  })

  it('moves a dropped folder under this folder', async () => {
    const target = h.factory('playlist-folder').make({ name: 'Target Folder', parent_id: null })
    const dragged = h.factory('playlist-folder').make({ parent_id: null })
    playlistFolderStore.state.folders = [target, dragged]
    const moveMock = h.mock(playlistFolderStore, 'moveFolderToFolder')

    h.render(Component, {
      props: { folder: target },
      global: {
        provide: {
          [DraggedPlaylistFolderKey as symbol]: ref(dragged),
        },
      },
    })

    const targetElement = screen.getByText(target.name).closest('.playlist-folder')!
    const dataTransfer = {
      dropEffect: '',
      types: ['application/x-koel.playlist-folder'],
      getData: () => JSON.stringify(dragged.id),
    }
    let wasDragOverPrevented = false
    targetElement.addEventListener('dragover', event => {
      wasDragOverPrevented = event.defaultPrevented
    })

    await fireEvent.dragOver(targetElement, { dataTransfer })
    expect(wasDragOverPrevented).toBe(true)
    await fireEvent.drop(targetElement, {
      dataTransfer: {
        types: ['application/x-koel.playlist-folder'],
        getData: () => JSON.stringify(dragged.id),
      },
    })

    expect(moveMock).toHaveBeenCalledWith(dragged, target)
  })

  it('does not expose a descendant as a folder drop target', async () => {
    const root = h.factory('playlist-folder').make({ parent_id: null })
    const child = h.factory('playlist-folder').make({ name: 'Child Folder', parent_id: root.id })
    playlistFolderStore.state.folders = [root, child]
    const ancestorDragOver = vi.fn()
    const dropTargetId = ref<string | null>(root.id)
    const { container } = h.render(Component, {
      props: { folder: child },
      global: {
        provide: {
          [DraggedPlaylistFolderKey as symbol]: ref(root),
          [PlaylistFolderDropTargetKey as symbol]: dropTargetId,
        },
      },
    })
    container.addEventListener('dragover', ancestorDragOver)

    const childElement = screen.getByText(child.name).closest('.playlist-folder')!
    await fireEvent.dragOver(childElement, {
      dataTransfer: {
        types: ['application/x-koel.playlist-folder'],
      },
    })

    expect(ancestorDragOver).not.toHaveBeenCalled()
    expect(dropTargetId.value).toBeNull()
    expect(childElement.classList.contains('droppable')).toBe(false)
  })

  it('keeps the ancestor path visible for a nested drop target', () => {
    const root = h.factory('playlist-folder').make({ name: 'Root Folder', parent_id: null })
    const child = h.factory('playlist-folder').make({ parent_id: root.id })
    playlistFolderStore.state.folders = [root, child]

    h.render(Component, {
      props: { folder: root },
      global: {
        provide: {
          [PlaylistFolderDropTargetKey as symbol]: ref(child.id),
        },
      },
    })

    expect(screen.getByText(root.name).closest('.playlist-folder')?.classList.contains('drop-target-path')).toBe(true)
  })

  it('keeps a nested folder as the drag payload without letting an ancestor replace it', async () => {
    const root = h.factory('playlist-folder').make({ name: 'Root Folder', parent_id: null })
    const child = h.factory('playlist-folder').make({ name: 'Child Folder', parent_id: root.id })
    playlistFolderStore.state.folders = [root, child]
    const setData = vi.fn()
    const draggedFolder = ref<PlaylistFolder | null>(null)

    h.render(Component, {
      props: { folder: root },
      global: {
        provide: {
          [DraggedPlaylistFolderKey as symbol]: draggedFolder,
        },
      },
    })

    await h.user.click(screen.getByText(root.name))
    await fireEvent.dragStart(screen.getByText(child.name).closest('.playlist-folder')!, {
      dataTransfer: {
        effectAllowed: '',
        types: [],
        setData,
        setDragImage: vi.fn(),
      },
    })

    expect(setData).toHaveBeenCalledOnce()
    expect(setData).toHaveBeenCalledWith('application/x-koel.playlist-folder', JSON.stringify(child.id))
    expect(draggedFolder.value).toMatchObject({ id: child.id })
  })
})
