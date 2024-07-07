import Router from '@/router'
import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { arrayify, eventBus } from '@/utils'
import { screen, waitFor } from '@testing-library/vue'
import { downloadService, playbackService } from '@/services'
import { favoriteStore, playlistStore, queueStore, songStore } from '@/stores'
import { DialogBoxStub, MessageToasterStub } from '@/__tests__/stubs'
import Component from './PlayableContextMenu.vue'

let playables: Playable[]

new class extends UnitTestCase {
  protected beforeEach () {
    super.beforeEach(() => queueStore.state.playables = [])
  }

  protected test () {
    it('plays', async () => {
      const playMock = this.mock(playbackService, 'play')
      const song = factory('song', { playback_state: 'Stopped' })
      await this.renderComponent(song)

      await this.user.click(screen.getByText('Play'))

      expect(playMock).toHaveBeenCalledWith(song)
    })

    it('pauses playback', async () => {
      const pauseMock = this.mock(playbackService, 'pause')
      await this.renderComponent(factory('song', { playback_state: 'Playing' }))

      await this.user.click(screen.getByText('Pause'))

      expect(pauseMock).toHaveBeenCalled()
    })

    it('resumes playback', async () => {
      const resumeMock = this.mock(playbackService, 'resume')
      await this.renderComponent(factory('song', { playback_state: 'Paused' }))

      await this.user.click(screen.getByText('Play'))

      expect(resumeMock).toHaveBeenCalled()
    })

    it('goes to album details screen', async () => {
      const goMock = this.mock(Router, 'go')
      const song = factory('song')
      await this.renderComponent(song)

      await this.user.click(screen.getByText('Go to Album'))

      expect(goMock).toHaveBeenCalledWith(`album/${song.album_id}`)
    })

    it('goes to artist details screen', async () => {
      const goMock = this.mock(Router, 'go')
      const song = factory('song')
      await this.renderComponent(song)

      await this.user.click(screen.getByText('Go to Artist'))

      expect(goMock).toHaveBeenCalledWith(`artist/${song.artist_id}`)
    })

    it('downloads', async () => {
      const downloadMock = this.mock(downloadService, 'fromPlayables')
      await this.renderComponent()

      await this.user.click(screen.getByText('Download'))

      expect(downloadMock).toHaveBeenCalledWith(playables)
    })

    it('queues', async () => {
      const queueMock = this.mock(queueStore, 'queue')
      await this.renderComponent()

      await this.user.click(screen.getByText('Queue'))

      expect(queueMock).toHaveBeenCalledWith(playables)
    })

    it('queues after current', async () => {
      this.fillQueue()
      const queueMock = this.mock(queueStore, 'queueAfterCurrent')
      await this.renderComponent()

      await this.user.click(screen.getByText('After Current'))

      expect(queueMock).toHaveBeenCalledWith(playables)
    })

    it('queues to bottom', async () => {
      this.fillQueue()
      const queueMock = this.mock(queueStore, 'queue')
      await this.renderComponent()

      await this.user.click(screen.getByText('Bottom of Queue'))

      expect(queueMock).toHaveBeenCalledWith(playables)
    })

    it('queues to top', async () => {
      this.fillQueue()
      const queueMock = this.mock(queueStore, 'queueToTop')
      await this.renderComponent()

      await this.user.click(screen.getByText('Top of Queue'))

      expect(queueMock).toHaveBeenCalledWith(playables)
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

      expect(removeMock).toHaveBeenCalledWith(playables)
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

      expect(likeMock).toHaveBeenCalledWith(playables)
    })

    it('does not have an option to add to favorites for Favorites screen', async () => {
      await this.router.activateRoute({
        path: '/favorites',
        screen: 'Favorites'
      })

      await this.renderComponent()

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

      expect(unlikeMock).toHaveBeenCalledWith(playables)
    })

    it('lists and adds to existing playlist', async () => {
      playlistStore.state.playlists = factory('playlist', 3)
      const addMock = this.mock(playlistStore, 'addContent')
      this.mock(MessageToasterStub.value, 'success')
      await this.renderComponent()

      playlistStore.state.playlists.forEach(playlist => screen.queryByText(playlist.name))

      await this.user.click(screen.getByText(playlistStore.state.playlists[0].name))

      expect(addMock).toHaveBeenCalledWith(playlistStore.state.playlists[0], playables)
    })

    it('does not list smart playlists', async () => {
      playlistStore.state.playlists = factory('playlist', 3)
      playlistStore.state.playlists.push(factory.states('smart')('playlist', { name: 'My Smart Playlist' }))

      await this.renderComponent()

      expect(screen.queryByText('My Smart Playlist')).toBeNull()
    })

    it('removes from playlist', async () => {
      const playlist = factory('playlist')
      playlistStore.state.playlists.push(playlist)

      await this.router.activateRoute({
        path: `/playlists/${playlist.id}`,
        screen: 'Playlist'
      }, { id: String(playlist.id) })

      await this.renderComponent()

      const removeContentMock = this.mock(playlistStore, 'removeContent')
      const emitMock = this.mock(eventBus, 'emit')

      await this.user.click(screen.getByText('Remove from Playlist'))

      await waitFor(() => {
        expect(removeContentMock).toHaveBeenCalledWith(playlist, playables)
        expect(emitMock).toHaveBeenCalledWith('PLAYLIST_CONTENT_REMOVED', playlist, playables)
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

      expect(emitMock).toHaveBeenCalledWith('MODAL_SHOW_EDIT_SONG_FORM', playables)
    })

    it('does not allow edit songs if current user is not admin', async () => {
      await this.be().renderComponent()
      expect(screen.queryByText('Edit…')).toBeNull()
    })

    it('has an option to copy shareable URL in Community edition', async () => {
      await this.renderComponent(factory('song'))
      screen.getByText('Copy Shareable URL')
    })

    it('has an option to copy shareable URL if song is public in Plus edition', async () => {
      this.enablePlusEdition()

      await this.renderComponent(factory('song', { is_public: true }))
      screen.getByText('Copy Shareable URL')
    })

    it('does not have an option to share if song is private in Plus edition', async () => {
      this.enablePlusEdition()

      await this.renderComponent(factory('song', { is_public: false }))
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
        expect(deleteMock).toHaveBeenCalledWith(playables)
        expect(toasterMock).toHaveBeenCalledWith('Deleted 5 songs from the filesystem.')
        expect(emitMock).toHaveBeenCalledWith('SONGS_DELETED', playables)
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

      expect(emitMock).toHaveBeenCalledWith('MODAL_SHOW_CREATE_PLAYLIST_FORM', null, playables)
    })

    it('makes songs private', async () => {
      this.enablePlusEdition()

      const user = factory('user')
      const songs = factory('song', 5, {
        is_public: true,
        owner_id: user.id
      })

      await this.be(user).renderComponent(songs)
      const privatizeMock = this.mock(songStore, 'privatize').mockResolvedValue(songs.map(song => song.id))

      await this.user.click(screen.getByText('Mark as Private'))

      expect(privatizeMock).toHaveBeenCalledWith(songs)
    })

    it('makes songs public', async () => {
      this.enablePlusEdition()

      const user = factory('user')
      const songs = factory('song', 5, {
        is_public: false,
        owner_id: user.id
      })

      await this.be(user).renderComponent(songs)
      const publicizeMock = this.mock(songStore, 'publicize').mockResolvedValue(songs.map(song => song.id))

      await this.user.click(screen.getByText('Unmark as Private'))

      expect(publicizeMock).toHaveBeenCalledWith(songs)
    })

    it('does not have an option to make songs public or private if current user is not owner', async () => {
      this.enablePlusEdition()

      const user = factory('user')
      const owner = factory('user')
      const songs = factory('song', 5, {
        is_public: false,
        owner_id: owner.id
      })

      await this.be(user).renderComponent(songs)

      expect(screen.queryByText('Unmark as Private')).toBeNull()
      expect(screen.queryByText('Mark as Private')).toBeNull()
    })

    it('has both options to make public and private if songs have mixed visibilities', async () => {
      this.enablePlusEdition()

      const owner = factory('user')
      const songs = factory('song', 2, {
        is_public: false,
        owner_id: owner.id
      }).concat(...factory('song', 3, {
        is_public: true,
        owner_id: owner.id
      }))

      await this.be(owner).renderComponent(songs)

      screen.getByText('Unmark as Private')
      screen.getByText('Mark as Private')
    })

    it('does not have an option to make songs public or private or Community edition', async () => {
      const owner = factory('user')
      const songs = factory('song', 5, {
        is_public: false,
        owner_id: owner.id
      })

      await this.be(owner).renderComponent(songs)

      expect(screen.queryByText('Unmark as Private')).toBeNull()
      expect(screen.queryByText('Mark as Private')).toBeNull()
    })
  }

  private async renderComponent (_playables?: MaybeArray<Playable>) {
    playables = arrayify(_playables || factory('song', 5))

    const rendered = this.render(Component)
    eventBus.emit('PLAYABLE_CONTEXT_MENU_REQUESTED', { pageX: 420, pageY: 42 } as MouseEvent, playables)
    await this.tick(2)

    return rendered
  }

  private fillQueue () {
    queueStore.state.playables = factory('song', 5)
    queueStore.state.playables[2].playback_state = 'Playing'
  }
}
