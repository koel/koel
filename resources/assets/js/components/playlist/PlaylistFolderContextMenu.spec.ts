import { expect, it } from 'vitest'
import { screen, waitFor } from '@testing-library/vue'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { MessageToasterStub } from '@/__tests__/stubs'
import { playlistStore } from '@/stores/playlistStore'
import { songStore } from '@/stores/songStore'
import { playbackService } from '@/services/playbackService'
import { eventBus } from '@/utils/eventBus'
import Router from '@/router'
import PlaylistFolderContextMenu from './PlaylistFolderContextMenu.vue'

new class extends UnitTestCase {
  protected test () {
    it('renames', async () => {
      const folder = factory('playlist-folder')
      await this.renderComponent(folder)
      const emitMock = this.mock(eventBus, 'emit')

      await this.user.click(screen.getByText('Rename'))

      expect(emitMock).toHaveBeenCalledWith('MODAL_SHOW_EDIT_PLAYLIST_FOLDER_FORM', folder)
    })

    it('deletes', async () => {
      const folder = factory('playlist-folder')
      await this.renderComponent(folder)
      const emitMock = this.mock(eventBus, 'emit')

      await this.user.click(screen.getByText('Delete'))

      expect(emitMock).toHaveBeenCalledWith('PLAYLIST_FOLDER_DELETE', folder)
    })

    it('plays', async () => {
      const folder = this.createPlayableFolder()
      const songs = factory('song', 3)
      const fetchMock = this.mock(songStore, 'fetchForPlaylistFolder').mockResolvedValue(songs)
      const queueMock = this.mock(playbackService, 'queueAndPlay')
      const goMock = this.mock(Router, 'go')
      await this.renderComponent(folder)

      await this.user.click(screen.getByText('Play All'))

      await waitFor(() => {
        expect(fetchMock).toHaveBeenCalledWith(folder)
        expect(queueMock).toHaveBeenCalledWith(songs)
        expect(goMock).toHaveBeenCalledWith('queue')
      })
    })

    it('warns if attempting to play with no songs in folder', async () => {
      const folder = this.createPlayableFolder()

      const fetchMock = this.mock(songStore, 'fetchForPlaylistFolder').mockResolvedValue([])
      const queueMock = this.mock(playbackService, 'queueAndPlay')
      const goMock = this.mock(Router, 'go')
      const warnMock = this.mock(MessageToasterStub.value, 'warning')

      await this.renderComponent(folder)

      await this.user.click(screen.getByText('Play All'))

      await waitFor(() => {
        expect(fetchMock).toHaveBeenCalledWith(folder)
        expect(queueMock).not.toHaveBeenCalled()
        expect(goMock).not.toHaveBeenCalled()
        expect(warnMock).toHaveBeenCalledWith('No songs available.')
      })
    })

    it('shuffles', async () => {
      const folder = this.createPlayableFolder()
      const songs = factory('song', 3)
      const fetchMock = this.mock(songStore, 'fetchForPlaylistFolder').mockResolvedValue(songs)
      const queueMock = this.mock(playbackService, 'queueAndPlay')
      const goMock = this.mock(Router, 'go')
      await this.renderComponent(folder)

      await this.user.click(screen.getByText('Shuffle All'))

      await waitFor(() => {
        expect(fetchMock).toHaveBeenCalledWith(folder)
        expect(queueMock).toHaveBeenCalledWith(songs, true)
        expect(goMock).toHaveBeenCalledWith('queue')
      })
    })

    it('does not show shuffle option if folder is empty', async () => {
      const folder = factory('playlist-folder')
      await this.renderComponent(folder)

      expect(screen.queryByText('Shuffle All')).toBeNull()
      expect(screen.queryByText('Play All')).toBeNull()
    })

    it('warns if attempting to shuffle with no songs in folder', async () => {
      const folder = this.createPlayableFolder()

      const fetchMock = this.mock(songStore, 'fetchForPlaylistFolder').mockResolvedValue([])
      const queueMock = this.mock(playbackService, 'queueAndPlay')
      const goMock = this.mock(Router, 'go')
      const warnMock = this.mock(MessageToasterStub.value, 'warning')

      await this.renderComponent(folder)

      await this.user.click(screen.getByText('Shuffle All'))

      await waitFor(() => {
        expect(fetchMock).toHaveBeenCalledWith(folder)
        expect(queueMock).not.toHaveBeenCalled()
        expect(goMock).not.toHaveBeenCalled()
        expect(warnMock).toHaveBeenCalledWith('No songs available.')
      })
    })
  }

  private async renderComponent (folder: PlaylistFolder) {
    this.render(PlaylistFolderContextMenu)
    eventBus.emit('PLAYLIST_FOLDER_CONTEXT_MENU_REQUESTED', { pageX: 420, pageY: 42 } as MouseEvent, folder)
    await this.tick(2)
  }

  private createPlayableFolder () {
    const folder = factory('playlist-folder')
    this.mock(playlistStore, 'byFolder', factory('playlist', 3, { folder_id: folder.id }))
    return folder
  }
}
