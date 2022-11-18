import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { arrayify, eventBus } from '@/utils'
import { fireEvent, waitFor } from '@testing-library/vue'
import { downloadService, playbackService } from '@/services'
import { favoriteStore, playlistStore, queueStore, songStore } from '@/stores'
import { DialogBoxStub, MessageToasterStub } from '@/__tests__/stubs'
import SongContextMenu from './SongContextMenu.vue'

let songs: Song[]

new class extends UnitTestCase {
  protected beforeEach () {
    super.beforeEach(() => queueStore.clear())
  }

  private async renderComponent (_songs?: Song | Song[]) {
    songs = arrayify(_songs || factory<Song>('song', 5))

    const rendered = this.render(SongContextMenu)
    eventBus.emit('SONG_CONTEXT_MENU_REQUESTED', { pageX: 420, pageY: 42 }, songs)
    await this.tick(2)

    return rendered
  }

  private fillQueue () {
    queueStore.state.songs = factory<Song>('song', 5)
    queueStore.state.songs[2].playback_state = 'Playing'
  }

  protected test () {
    it('queues and plays', async () => {
      const queueMock = this.mock(queueStore, 'queueIfNotQueued')
      const playMock = this.mock(playbackService, 'play')
      const song = factory<Song>('song', { playback_state: 'Stopped' })
      const { getByText } = await this.renderComponent(song)

      await fireEvent.click(getByText('Play'))

      expect(queueMock).toHaveBeenCalledWith(song)
      expect(playMock).toHaveBeenCalledWith(song)
    })

    it('pauses playback', async () => {
      const pauseMock = this.mock(playbackService, 'pause')
      const { getByText } = await this.renderComponent(factory<Song>('song', { playback_state: 'Playing' }))

      await fireEvent.click(getByText('Pause'))

      expect(pauseMock).toHaveBeenCalled()
    })

    it('resumes playback', async () => {
      const resumeMock = this.mock(playbackService, 'resume')
      const { getByText } = await this.renderComponent(factory<Song>('song', { playback_state: 'Paused' }))

      await fireEvent.click(getByText('Play'))

      expect(resumeMock).toHaveBeenCalled()
    })

    it('goes to album details screen', async () => {
      const goMock = this.mock(this.router, 'go')
      const { getByText } = await this.renderComponent(factory<Song>('song'))

      await fireEvent.click(getByText('Go to Album'))

      expect(goMock).toHaveBeenCalledWith(`album/${songs[0].album_id}`)
    })

    it('goes to artist details screen', async () => {
      const goMock = this.mock(this.router, 'go')
      const { getByText } = await this.renderComponent(factory<Song>('song'))

      await fireEvent.click(getByText('Go to Artist'))

      expect(goMock).toHaveBeenCalledWith(`artist/${songs[0].artist_id}`)
    })

    it('downloads', async () => {
      const downloadMock = this.mock(downloadService, 'fromSongs')
      const { getByText } = await this.renderComponent()

      await fireEvent.click(getByText('Download'))

      expect(downloadMock).toHaveBeenCalledWith(songs)
    })

    it('queues', async () => {
      const queueMock = this.mock(queueStore, 'queue')
      const { getByText } = await this.renderComponent()

      await fireEvent.click(getByText('Queue'))

      expect(queueMock).toHaveBeenCalledWith(songs)
    })

    it('queues after current song', async () => {
      this.fillQueue()
      const queueMock = this.mock(queueStore, 'queueAfterCurrent')
      const { getByText } = await this.renderComponent()

      await fireEvent.click(getByText('After Current Song'))

      expect(queueMock).toHaveBeenCalledWith(songs)
    })

    it('queues to bottom', async () => {
      this.fillQueue()
      const queueMock = this.mock(queueStore, 'queue')
      const { getByText } = await this.renderComponent()

      await fireEvent.click(getByText('Bottom of Queue'))

      expect(queueMock).toHaveBeenCalledWith(songs)
    })

    it('queues to top', async () => {
      this.fillQueue()
      const queueMock = this.mock(queueStore, 'queueToTop')
      const { getByText } = await this.renderComponent()

      await fireEvent.click(getByText('Top of Queue'))

      expect(queueMock).toHaveBeenCalledWith(songs)
    })

    it('removes from queue', async () => {
      this.fillQueue()
      const removeMock = this.mock(queueStore, 'unqueue')

      await this.router.activateRoute({
        path: '/queue',
        screen: 'Queue'
      })

      const { getByText } = await this.renderComponent()

      await fireEvent.click(getByText('Remove from Queue'))

      expect(removeMock).toHaveBeenCalledWith(songs)
    })

    it('does not show "Remove from Queue" when not on Queue screen', async () => {
      this.fillQueue()

      await this.router.activateRoute({
        path: '/songs',
        screen: 'Songs'
      })

      const { queryByText } = await this.renderComponent()

      expect(queryByText('Remove from Queue')).toBeNull()
    })

    it('adds to favorites', async () => {
      const likeMock = this.mock(favoriteStore, 'like')
      const { getByText } = await this.renderComponent()

      await fireEvent.click(getByText('Favorites'))

      expect(likeMock).toHaveBeenCalledWith(songs)
    })

    it('does not have an option to add to favorites for Favorites screen', async () => {
      await this.router.activateRoute({
        path: '/favorites',
        screen: 'Favorites'
      })

      const { queryByText } = await this.renderComponent()

      expect(queryByText('Favorites')).toBeNull()
    })

    it('removes from favorites', async () => {
      const unlikeMock = this.mock(favoriteStore, 'unlike')

      await this.router.activateRoute({
        path: '/favorites',
        screen: 'Favorites'
      })

      const { getByText } = await this.renderComponent()

      await fireEvent.click(getByText('Remove from Favorites'))

      expect(unlikeMock).toHaveBeenCalledWith(songs)
    })

    it('lists and adds to existing playlist', async () => {
      playlistStore.state.playlists = factory<Playlist>('playlist', 3)
      const addMock = this.mock(playlistStore, 'addSongs')
      this.mock(MessageToasterStub.value, 'success')
      const { queryByText, getByText } = await this.renderComponent()

      playlistStore.state.playlists.forEach(playlist => queryByText(playlist.name))

      await fireEvent.click(getByText(playlistStore.state.playlists[0].name))

      expect(addMock).toHaveBeenCalledWith(playlistStore.state.playlists[0], songs)
    })

    it('does not list smart playlists', async () => {
      playlistStore.state.playlists = factory<Playlist>('playlist', 3)
      playlistStore.state.playlists.push(factory.states('smart')<Playlist>('playlist', { name: 'My Smart Playlist' }))

      const { queryByText } = await this.renderComponent()

      expect(queryByText('My Smart Playlist')).toBeNull()
    })

    it('removes from playlist', async () => {
      const playlist = factory<Playlist>('playlist')
      playlistStore.state.playlists.push(playlist)

      await this.router.activateRoute({
        path: `/playlists/${playlist.id}`,
        screen: 'Playlist'
      }, { id: String(playlist.id) })

      const { getByText } = await this.renderComponent()

      const removeSongsMock = this.mock(playlistStore, 'removeSongs')
      const emitMock = this.mock(eventBus, 'emit')

      await fireEvent.click(getByText('Remove from Playlist'))

      await waitFor(() => {
        expect(removeSongsMock).toHaveBeenCalledWith(playlist, songs)
        expect(emitMock).toHaveBeenCalledWith('PLAYLIST_SONGS_REMOVED', playlist, songs)
      })
    })

    it('does not have an option to remove from playlist if not on Playlist screen', async () => {
      await this.router.activateRoute({
        path: '/songs',
        screen: 'Songs'
      })

      const { queryByText } = await this.renderComponent()

      expect(queryByText('Remove from Playlist')).toBeNull()
    })

    it('allows edit songs if current user is admin', async () => {
      const { getByText } = await this.actingAsAdmin().renderComponent()

      // mock after render to ensure that the component is mounted properly
      const emitMock = this.mock(eventBus, 'emit')
      await fireEvent.click(getByText('Edit'))

      expect(emitMock).toHaveBeenCalledWith('MODAL_SHOW_EDIT_SONG_FORM', songs)
    })

    it('does not allow edit songs if current user is not admin', async () => {
      const { queryByText } = await this.actingAs().renderComponent()
      expect(queryByText('Edit')).toBeNull()
    })

    it('has an option to copy shareable URL', async () => {
      const { getByText } = await this.renderComponent(factory<Song>('song'))

      getByText('Copy Shareable URL')
    })

    it('deletes song', async () => {
      const confirmMock = this.mock(DialogBoxStub.value, 'confirm', true)
      const deleteMock = this.mock(songStore, 'deleteFromFilesystem')
      const { getByText } = await this.actingAsAdmin().renderComponent()

      const emitMock = this.mock(eventBus, 'emit')

      await fireEvent.click(getByText('Delete from Filesystem'))

      await waitFor(() => {
        expect(confirmMock).toHaveBeenCalled()
        expect(deleteMock).toHaveBeenCalledWith(songs)
        expect(emitMock).toHaveBeenCalledWith('SONGS_DELETED', songs)
      })
    })

    it('does not have an option to delete songs if current user is not admin', async () => {
      const { queryByText } = await this.actingAs().renderComponent()
      expect(queryByText('Delete from Filesystem')).toBeNull()
    })
  }
}
