import Router from '@/router'
import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { arrayify, eventBus, use } from '@/utils'
import { screen, waitFor } from '@testing-library/vue'
import { downloadService, playbackService } from '@/services'
import { favoriteStore, playlistStore, queueStore, songStore } from '@/stores'
import { DialogBoxStub, MessageToasterStub } from '@/__tests__/stubs'
import SongContextMenu from './SongContextMenu.vue'

let songs: Song[]

new class extends UnitTestCase {
  protected beforeEach () {
    super.beforeEach(() => queueStore.state.songs = [])
  }

  private async renderComponent (_songs?: Song | Song[]) {
    songs = arrayify(_songs || factory<Song>('song', 5))

    const rendered = this.render(SongContextMenu)
    eventBus.emit('SONG_CONTEXT_MENU_REQUESTED', { pageX: 420, pageY: 42 } as MouseEvent, songs)
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
      await this.renderComponent(song)

      await this.user.click(screen.getByText('Play'))

      expect(queueMock).toHaveBeenCalledWith(song)
      expect(playMock).toHaveBeenCalledWith(song)
    })

    it('pauses playback', async () => {
      const pauseMock = this.mock(playbackService, 'pause')
      await this.renderComponent(factory<Song>('song', { playback_state: 'Playing' }))

      await this.user.click(screen.getByText('Pause'))

      expect(pauseMock).toHaveBeenCalled()
    })

    it('resumes playback', async () => {
      const resumeMock = this.mock(playbackService, 'resume')
      await this.renderComponent(factory<Song>('song', { playback_state: 'Paused' }))

      await this.user.click(screen.getByText('Play'))

      expect(resumeMock).toHaveBeenCalled()
    })

    it('goes to album details screen', async () => {
      const goMock = this.mock(Router, 'go')
      await this.renderComponent(factory<Song>('song'))

      await this.user.click(screen.getByText('Go to Album'))

      expect(goMock).toHaveBeenCalledWith(`album/${songs[0].album_id}`)
    })

    it('goes to artist details screen', async () => {
      const goMock = this.mock(Router, 'go')
      await this.renderComponent(factory<Song>('song'))

      await this.user.click(screen.getByText('Go to Artist'))

      expect(goMock).toHaveBeenCalledWith(`artist/${songs[0].artist_id}`)
    })

    it('downloads', async () => {
      const downloadMock = this.mock(downloadService, 'fromSongs')
      await this.renderComponent()

      await this.user.click(screen.getByText('Download'))

      expect(downloadMock).toHaveBeenCalledWith(songs)
    })

    it('queues', async () => {
      const queueMock = this.mock(queueStore, 'queue')
      await this.renderComponent()

      await this.user.click(screen.getByText('Queue'))

      expect(queueMock).toHaveBeenCalledWith(songs)
    })

    it('queues after current song', async () => {
      this.fillQueue()
      const queueMock = this.mock(queueStore, 'queueAfterCurrent')
      await this.renderComponent()

      await this.user.click(screen.getByText('After Current Song'))

      expect(queueMock).toHaveBeenCalledWith(songs)
    })

    it('queues to bottom', async () => {
      this.fillQueue()
      const queueMock = this.mock(queueStore, 'queue')
      await this.renderComponent()

      await this.user.click(screen.getByText('Bottom of Queue'))

      expect(queueMock).toHaveBeenCalledWith(songs)
    })

    it('queues to top', async () => {
      this.fillQueue()
      const queueMock = this.mock(queueStore, 'queueToTop')
      await this.renderComponent()

      await this.user.click(screen.getByText('Top of Queue'))

      expect(queueMock).toHaveBeenCalledWith(songs)
    })

    it('removes from queue', async () => {
      this.fillQueue()
      const removeMock = this.mock(queueStore, 'unqueue')

      await this.router.activateRoute({
        path: '/queue',
        screen: 'Queue'
      })

      await this.renderComponent()

      await this.user.click(screen.getByText('Remove from Queue'))

      expect(removeMock).toHaveBeenCalledWith(songs)
    })

    it('does not show "Remove from Queue" when not on Queue screen', async () => {
      this.fillQueue()

      await this.router.activateRoute({
        path: '/songs',
        screen: 'Songs'
      })

      await this.renderComponent()

      expect(screen.queryByText('Remove from Queue')).toBeNull()
    })

    it('adds to favorites', async () => {
      const likeMock = this.mock(favoriteStore, 'like')
      await this.renderComponent()

      await this.user.click(screen.getByText('Favorites'))

      expect(likeMock).toHaveBeenCalledWith(songs)
    })

    it('does not have an option to add to favorites for Favorites screen', async () => {
      await this.router.activateRoute({
        path: '/favorites',
        screen: 'Favorites'
      })

      this.renderComponent()

      expect(screen.queryByText('Favorites')).toBeNull()
    })

    it('removes from favorites', async () => {
      const unlikeMock = this.mock(favoriteStore, 'unlike')

      await this.router.activateRoute({
        path: '/favorites',
        screen: 'Favorites'
      })

      await this.renderComponent()

      await this.user.click(screen.getByText('Remove from Favorites'))

      expect(unlikeMock).toHaveBeenCalledWith(songs)
    })

    it('lists and adds to existing playlist', async () => {
      playlistStore.state.playlists = factory<Playlist>('playlist', 3)
      const addMock = this.mock(playlistStore, 'addSongs')
      this.mock(MessageToasterStub.value, 'success')
      await this.renderComponent()

      playlistStore.state.playlists.forEach(playlist => screen.queryByText(playlist.name))

      await this.user.click(screen.getByText(playlistStore.state.playlists[0].name))

      expect(addMock).toHaveBeenCalledWith(playlistStore.state.playlists[0], songs)
    })

    it('does not list smart playlists', async () => {
      playlistStore.state.playlists = factory<Playlist>('playlist', 3)
      playlistStore.state.playlists.push(factory.states('smart')<Playlist>('playlist', { name: 'My Smart Playlist' }))

      await this.renderComponent()

      expect(screen.queryByText('My Smart Playlist')).toBeNull()
    })

    it('removes from playlist', async () => {
      const playlist = factory<Playlist>('playlist')
      playlistStore.state.playlists.push(playlist)

      await this.router.activateRoute({
        path: `/playlists/${playlist.id}`,
        screen: 'Playlist'
      }, { id: String(playlist.id) })

      await this.renderComponent()

      const removeSongsMock = this.mock(playlistStore, 'removeSongs')
      const emitMock = this.mock(eventBus, 'emit')

      await this.user.click(screen.getByText('Remove from Playlist'))

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

      await this.renderComponent()

      expect(screen.queryByText('Remove from Playlist')).toBeNull()
    })

    it('allows edit songs if current user is admin', async () => {
      await this.beAdmin().renderComponent()

      // mock after render to ensure that the component is mounted properly
      const emitMock = this.mock(eventBus, 'emit')
      await this.user.click(screen.getByText('Edit…'))

      expect(emitMock).toHaveBeenCalledWith('MODAL_SHOW_EDIT_SONG_FORM', songs)
    })

    it('does not allow edit songs if current user is not admin', async () => {
      await this.be().renderComponent()
      expect(screen.queryByText('Edit…')).toBeNull()
    })

    it('has an option to copy shareable URL in Community edition', async () => {
      await this.renderComponent(factory<Song>('song'))
      screen.getByText('Copy Shareable URL')
    })

    it('has an option to copy shareable URL if song is public in Plus edition', async () => {
      this.enablePlusEdition()

      await this.renderComponent(factory<Song>('song', { is_public: true }))
      screen.getByText('Copy Shareable URL')
    })

    it('does not have an option to share if song is private in Plus edition', async () => {
      this.enablePlusEdition()

      await this.renderComponent(factory<Song>('song', { is_public: false }))
      expect(screen.queryByText('Copy Shareable URL')).toBeNull()
    })

    it('deletes song', async () => {
      const confirmMock = this.mock(DialogBoxStub.value, 'confirm', true)
      const toasterMock = this.mock(MessageToasterStub.value, 'success')
      const deleteMock = this.mock(songStore, 'deleteFromFilesystem')
      await this.beAdmin().renderComponent()

      const emitMock = this.mock(eventBus, 'emit')

      await this.user.click(screen.getByText('Delete from Filesystem'))

      await waitFor(() => {
        expect(confirmMock).toHaveBeenCalled()
        expect(deleteMock).toHaveBeenCalledWith(songs)
        expect(toasterMock).toHaveBeenCalledWith('Deleted 5 songs from the filesystem.')
        expect(emitMock).toHaveBeenCalledWith('SONGS_DELETED', songs)
      })
    })

    it('does not have an option to delete songs if current user is not admin', async () => {
      await this.be().renderComponent()
      expect(screen.queryByText('Delete from Filesystem')).toBeNull()
    })

    it('creates playlist from selected songs', async () => {
      await this.be().renderComponent()

      // mock after render to ensure that the component is mounted properly
      const emitMock = this.mock(eventBus, 'emit')

      await this.user.click(screen.getByText('New Playlist…'))

      expect(emitMock).toHaveBeenCalledWith('MODAL_SHOW_CREATE_PLAYLIST_FORM', null, songs)
    })

    it('makes songs private', async () => {
      this.enablePlusEdition()

      const user = factory<User>('user')
      const songs = factory<Song>('song', 5, {
        is_public: true,
        owner_id: user.id
      })

      await this.be(user).renderComponent(songs)
      const privatizeMock = this.mock(songStore, 'privatize')

      await this.user.click(screen.getByText('Mark as Private'))

      expect(privatizeMock).toHaveBeenCalledWith(songs)
    })

    it('makes songs public', async () => {
      this.enablePlusEdition()

      const user = factory<User>('user')
      const songs = factory<Song>('song', 5, {
        is_public: false,
        owner_id: user.id
      })

      await this.be(user).renderComponent(songs)
      const publicizeMock = this.mock(songStore, 'publicize')

      await this.user.click(screen.getByText('Unmark as Private'))

      expect(publicizeMock).toHaveBeenCalledWith(songs)
    })

    it('does not have an option to make songs public or private if current user is not owner', async () => {
      this.enablePlusEdition()

      const user = factory<User>('user')
      const owner = factory<User>('user')
      const songs = factory<Song>('song', 5, {
        is_public: false,
        owner_id: owner.id
      })

      await this.be(user).renderComponent(songs)

      expect(screen.queryByText('Unmark as Private')).toBeNull()
      expect(screen.queryByText('Mark as Private')).toBeNull()
    })

    it('has both options to make public and private if songs have mixed visibilities', async () => {
      this.enablePlusEdition()

      const owner = factory<User>('user')
      const songs = factory<Song>('song', 2, {
        is_public: false,
        owner_id: owner.id
      }).concat(...factory<Song>('song', 3, {
        is_public: true,
        owner_id: owner.id
      }))

      await this.be(owner).renderComponent(songs)

      screen.getByText('Unmark as Private')
      screen.getByText('Mark as Private')
    })

    it('does not have an option to make songs public or private oin Community edition', async () => {
      const owner = factory<User>('user')
      const songs = factory<Song>('song', 5, {
        is_public: false,
        owner_id: owner.id
      })

      await this.be(owner).renderComponent(songs)

      expect(screen.queryByText('Unmark as Private')).toBeNull()
      expect(screen.queryByText('Mark as Private')).toBeNull()
    })
  }
}
