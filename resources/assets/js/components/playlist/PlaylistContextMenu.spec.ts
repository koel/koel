import Router from '@/router'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { eventBus } from '@/utils'
import factory from '@/__tests__/factory'
import { screen, waitFor } from '@testing-library/vue'
import { songStore, userStore } from '@/stores'
import { playbackService, playlistCollaborationService } from '@/services'
import { queueStore } from '@/stores'
import { MessageToasterStub } from '@/__tests__/stubs'
import PlaylistContextMenu from './PlaylistContextMenu.vue'

new class extends UnitTestCase {
  private async renderComponent (playlist: Playlist, user: User | null = null) {
    userStore.state.current = user || factory<User>('user', {
      id: playlist.user_id
    })

    this.render(PlaylistContextMenu)
    eventBus.emit('PLAYLIST_CONTEXT_MENU_REQUESTED', { pageX: 420, pageY: 42 } as MouseEvent, playlist)
    await this.tick(2)
  }

  protected test () {
    it('edits a standard playlist', async () => {
      const playlist = factory<Playlist>('playlist')
      await this.renderComponent(playlist)
      const emitMock = this.mock(eventBus, 'emit')

      await this.user.click(screen.getByText('Edit…'))

      expect(emitMock).toHaveBeenCalledWith('MODAL_SHOW_EDIT_PLAYLIST_FORM', playlist)
    })

    it('edits a smart playlist', async () => {
      const playlist = factory.states('smart')<Playlist>('playlist')
      await this.renderComponent(playlist)
      const emitMock = this.mock(eventBus, 'emit')

      await this.user.click(screen.getByText('Edit…'))

      expect(emitMock).toHaveBeenCalledWith('MODAL_SHOW_EDIT_PLAYLIST_FORM', playlist)
    })

    it('deletes a playlist', async () => {
      const playlist = factory<Playlist>('playlist')
      await this.renderComponent(playlist)
      const emitMock = this.mock(eventBus, 'emit')

      await this.user.click(screen.getByText('Delete'))

      expect(emitMock).toHaveBeenCalledWith('PLAYLIST_DELETE', playlist)
    })

    it('plays', async () => {
      const playlist = factory<Playlist>('playlist')
      const songs = factory<Song>('song', 3)
      const fetchMock = this.mock(songStore, 'fetchForPlaylist').mockResolvedValue(songs)
      const queueMock = this.mock(playbackService, 'queueAndPlay')
      const goMock = this.mock(Router, 'go')
      await this.renderComponent(playlist)

      await this.user.click(screen.getByText('Play'))

      await waitFor(() => {
        expect(fetchMock).toHaveBeenCalledWith(playlist)
        expect(queueMock).toHaveBeenCalledWith(songs)
        expect(goMock).toHaveBeenCalledWith('queue')
      })
    })

    it('warns if attempting to play an empty playlist', async () => {
      const playlist = factory<Playlist>('playlist')
      const fetchMock = this.mock(songStore, 'fetchForPlaylist').mockResolvedValue([])
      const queueMock = this.mock(playbackService, 'queueAndPlay')
      const goMock = this.mock(Router, 'go')
      const warnMock = this.mock(MessageToasterStub.value, 'warning')

      await this.renderComponent(playlist)

      await this.user.click(screen.getByText('Play'))

      await waitFor(() => {
        expect(fetchMock).toHaveBeenCalledWith(playlist)
        expect(queueMock).not.toHaveBeenCalled()
        expect(goMock).not.toHaveBeenCalled()
        expect(warnMock).toHaveBeenCalledWith('The playlist is empty.')
      })
    })

    it('shuffles', async () => {
      const playlist = factory<Playlist>('playlist')
      const songs = factory<Song>('song', 3)
      const fetchMock = this.mock(songStore, 'fetchForPlaylist').mockResolvedValue(songs)
      const queueMock = this.mock(playbackService, 'queueAndPlay')
      const goMock = this.mock(Router, 'go')
      await this.renderComponent(playlist)

      await this.user.click(screen.getByText('Shuffle'))

      await waitFor(() => {
        expect(fetchMock).toHaveBeenCalledWith(playlist)
        expect(queueMock).toHaveBeenCalledWith(songs, true)
        expect(goMock).toHaveBeenCalledWith('queue')
      })
    })

    it('warns if attempting to shuffle an empty playlist', async () => {
      const playlist = factory<Playlist>('playlist')
      const fetchMock = this.mock(songStore, 'fetchForPlaylist').mockResolvedValue([])
      const queueMock = this.mock(playbackService, 'queueAndPlay')
      const goMock = this.mock(Router, 'go')
      const warnMock = this.mock(MessageToasterStub.value, 'warning')

      await this.renderComponent(playlist)

      await this.user.click(screen.getByText('Shuffle'))

      await waitFor(() => {
        expect(fetchMock).toHaveBeenCalledWith(playlist)
        expect(queueMock).not.toHaveBeenCalled()
        expect(goMock).not.toHaveBeenCalled()
        expect(warnMock).toHaveBeenCalledWith('The playlist is empty.')
      })
    })

    it('queues', async () => {
      const playlist = factory<Playlist>('playlist')
      const songs = factory<Song>('song', 3)
      const fetchMock = this.mock(songStore, 'fetchForPlaylist').mockResolvedValue(songs)
      const queueMock = this.mock(queueStore, 'queueAfterCurrent')
      const toastMock = this.mock(MessageToasterStub.value, 'success')
      await this.renderComponent(playlist)

      await this.user.click(screen.getByText('Add to Queue'))

      await waitFor(() => {
        expect(fetchMock).toHaveBeenCalledWith(playlist)
        expect(queueMock).toHaveBeenCalledWith(songs)
        expect(toastMock).toHaveBeenCalledWith('Playlist added to queue.')
      })
    })

    it('does not have an option to edit or delete if the playlist is not owned by the current user', async () => {
      const user = factory<User>('user')
      const playlist = factory<Playlist>('playlist', {
        user_id: user.id + 1
      })

      await this.renderComponent(playlist, user)

      expect(screen.queryByText('Edit…')).toBeNull()
      expect(screen.queryByText('Delete')).toBeNull()
    })

    it('invites collaborators', async () => {
      this.enablePlusEdition()
      const playlist = factory<Playlist>('playlist')
      await this.renderComponent(playlist)

      const createInviteLinkMock = this.mock(playlistCollaborationService, 'createInviteLink')
        .mockResolvedValue('https://koel.app/invite/123')

      await this.user.click(screen.getByText('Invite Collaborators'))

      await waitFor(async () => {
        expect(createInviteLinkMock).toHaveBeenCalledWith(playlist)
        expect(await navigator.clipboard.readText()).equal('https://koel.app/invite/123')
      })
    })
  }
}
