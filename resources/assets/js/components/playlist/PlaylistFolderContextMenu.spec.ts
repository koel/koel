import { describe, expect, it } from 'vitest'
import { screen, waitFor } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { MessageToasterStub } from '@/__tests__/stubs'
import { playlistStore } from '@/stores/playlistStore'
import { playableStore } from '@/stores/playableStore'
import { playbackService } from '@/services/QueuePlaybackService'
import { eventBus } from '@/utils/eventBus'
import Router from '@/router'
import { playlistFolderStore } from '@/stores/playlistFolderStore'
import Component from './PlaylistFolderContextMenu.vue'

describe('playlistFolderContextMenu.vue', () => {
  const h = createHarness()

  const renderComponent = async (folder?: PlaylistFolder) => {
    folder = folder || h.factory('playlist-folder')

    const rendered = h.render(Component, {
      props: {
        folder,
      },
    })

    return {
      ...rendered,
      folder,
    }
  }

  const createPlayableFolder = () => {
    const folder = h.factory('playlist-folder')
    h.mock(playlistStore, 'byFolder', h.factory('playlist', 3, { folder_id: folder.id }))
    return folder
  }

  it('renames', async () => {
    const { folder } = await renderComponent()
    const emitMock = h.mock(eventBus, 'emit')

    await h.user.click(screen.getByText('Rename'))

    expect(emitMock).toHaveBeenCalledWith('MODAL_SHOW_EDIT_PLAYLIST_FOLDER_FORM', folder)
  })

  it('deletes', async () => {
    const { folder } = await renderComponent()
    const deleteMock = h.mock(playlistFolderStore, 'delete')

    await h.user.click(screen.getByText('Delete'))
    expect(deleteMock).toHaveBeenCalledWith(folder)
  })

  it('plays', async () => {
    h.createAudioPlayer()

    const songs = h.factory('song', 3)
    const fetchMock = h.mock(playableStore, 'fetchForPlaylistFolder').mockResolvedValue(songs)
    const queueMock = h.mock(playbackService, 'queueAndPlay')
    const goMock = h.mock(Router, 'go')
    const { folder } = await renderComponent(createPlayableFolder())

    await h.user.click(screen.getByText('Play All'))

    await waitFor(() => {
      expect(fetchMock).toHaveBeenCalledWith(folder)
      expect(queueMock).toHaveBeenCalledWith(songs)
      expect(goMock).toHaveBeenCalledWith('/#/queue')
    })
  })

  it('warns if attempting to play with no songs in folder', async () => {
    h.createAudioPlayer()

    const fetchMock = h.mock(playableStore, 'fetchForPlaylistFolder').mockResolvedValue([])
    const queueMock = h.mock(playbackService, 'queueAndPlay')
    const goMock = h.mock(Router, 'go')
    const warnMock = h.mock(MessageToasterStub.value, 'warning')

    const { folder } = await renderComponent(createPlayableFolder())

    await h.user.click(screen.getByText('Play All'))

    await waitFor(() => {
      expect(fetchMock).toHaveBeenCalledWith(folder)
      expect(queueMock).not.toHaveBeenCalled()
      expect(goMock).not.toHaveBeenCalled()
      expect(warnMock).toHaveBeenCalledWith('No songs available.')
    })
  })

  it('shuffles', async () => {
    h.createAudioPlayer()

    const songs = h.factory('song', 3)
    const fetchMock = h.mock(playableStore, 'fetchForPlaylistFolder').mockResolvedValue(songs)
    const queueMock = h.mock(playbackService, 'queueAndPlay')
    const goMock = h.mock(Router, 'go')

    const { folder } = await renderComponent(createPlayableFolder())

    await h.user.click(screen.getByText('Shuffle All'))

    await waitFor(() => {
      expect(fetchMock).toHaveBeenCalledWith(folder)
      expect(queueMock).toHaveBeenCalledWith(songs, true)
      expect(goMock).toHaveBeenCalledWith('/#/queue')
    })
  })

  it('does not show shuffle option if folder is empty', async () => {
    await renderComponent()

    expect(screen.queryByText('Shuffle All')).toBeNull()
    expect(screen.queryByText('Play All')).toBeNull()
  })

  it('warns if attempting to shuffle with no songs in folder', async () => {
    h.createAudioPlayer()

    const fetchMock = h.mock(playableStore, 'fetchForPlaylistFolder').mockResolvedValue([])
    const queueMock = h.mock(playbackService, 'queueAndPlay')
    const goMock = h.mock(Router, 'go')
    const warnMock = h.mock(MessageToasterStub.value, 'warning')

    const { folder } = await renderComponent(createPlayableFolder())

    await h.user.click(screen.getByText('Shuffle All'))

    await waitFor(() => {
      expect(fetchMock).toHaveBeenCalledWith(folder)
      expect(queueMock).not.toHaveBeenCalled()
      expect(goMock).not.toHaveBeenCalled()
      expect(warnMock).toHaveBeenCalledWith('No songs available.')
    })
  })
})
