import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { eventBus } from '@/utils'
import factory from '@/__tests__/factory'
import { fireEvent, waitFor } from '@testing-library/vue'
import { playlistStore, songStore } from '@/stores'
import { playbackService } from '@/services'
import PlaylistFolderContextMenu from './PlaylistFolderContextMenu.vue'

new class extends UnitTestCase {
  private async renderComponent (folder: PlaylistFolder) {
    const rendered = await this.render(PlaylistFolderContextMenu)
    eventBus.emit('PLAYLIST_FOLDER_CONTEXT_MENU_REQUESTED', { pageX: 420, pageY: 42 }, folder)
    await this.tick(2)
    return rendered
  }

  protected test () {
    it('renames', async () => {
      const folder = factory<PlaylistFolder>('playlist-folder')
      const { getByText } = await this.renderComponent(folder)
      const emitMock = this.mock(eventBus, 'emit')

      await fireEvent.click(getByText('Rename'))

      expect(emitMock).toHaveBeenCalledWith('MODAL_SHOW_EDIT_PLAYLIST_FOLDER_FORM', folder)
    })

    it('deletes', async () => {
      const folder = factory<PlaylistFolder>('playlist-folder')
      const { getByText } = await this.renderComponent(folder)
      const emitMock = this.mock(eventBus, 'emit')

      await fireEvent.click(getByText('Delete'))

      expect(emitMock).toHaveBeenCalledWith('PLAYLIST_FOLDER_DELETE', folder)
    })

    it('plays', async () => {
      const folder = this.createPlayableFolder()
      const songs = factory<Song>('song', 3)
      const fetchMock = this.mock(songStore, 'fetchForPlaylistFolder').mockResolvedValue(songs)
      const queueMock = this.mock(playbackService, 'queueAndPlay')
      const goMock = this.mock(this.router, 'go')
      const { getByText } = await this.renderComponent(folder)

      await fireEvent.click(getByText('Play All'))

      await waitFor(() => {
        expect(fetchMock).toHaveBeenCalledWith(folder)
        expect(queueMock).toHaveBeenCalledWith(songs)
        expect(goMock).toHaveBeenCalledWith('queue')
      })
    })

    it('shuffles', async () => {
      const folder = this.createPlayableFolder()
      const songs = factory<Song>('song', 3)
      const fetchMock = this.mock(songStore, 'fetchForPlaylistFolder').mockResolvedValue(songs)
      const queueMock = this.mock(playbackService, 'queueAndPlay')
      const goMock = this.mock(this.router, 'go')
      const { getByText } = await this.renderComponent(folder)

      await fireEvent.click(getByText('Shuffle All'))

      await waitFor(() => {
        expect(fetchMock).toHaveBeenCalledWith(folder)
        expect(queueMock).toHaveBeenCalledWith(songs, true)
        expect(goMock).toHaveBeenCalledWith('queue')
      })
    })

    it('does not show shuffle option if folder is empty', async () => {
      const folder = factory<PlaylistFolder>('playlist-folder')
      const { queryByText } = await this.renderComponent(folder)

      expect(queryByText('Shuffle All')).toBeNull()
      expect(queryByText('Play All')).toBeNull()
    })
  }

  private createPlayableFolder () {
    const folder = factory<PlaylistFolder>('playlist-folder')
    this.mock(playlistStore, 'byFolder', factory<Playlist>('playlist', 3, { folder_id: folder.id }))
    return folder
  }
}
