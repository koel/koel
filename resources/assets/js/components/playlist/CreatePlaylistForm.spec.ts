import { expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { playlistStore } from '@/stores'
import factory from '@/__tests__/factory'
import { ref } from 'vue'
import { ModalContextKey } from '@/symbols'
import CreatePlaylistForm from './CreatePlaylistForm.vue'

new class extends UnitTestCase {
  protected test () {
    it('creates playlist with no songs', async () => {
      const folder = factory<PlaylistFolder>('playlist-folder')
      const storeMock = this.mock(playlistStore, 'store').mockResolvedValue(factory<Playlist>('playlist'))

      this.render(CreatePlaylistForm, {
        global: {
          provide: {
            [<symbol>ModalContextKey]: [ref({ folder })]
          }
        }
      })

      expect(screen.queryByTestId('from-songs')).toBeNull()

      await this.type(screen.getByPlaceholderText('Playlist name'), 'My playlist')
      await this.user.click(screen.getByRole('button', { name: 'Save' }))

      expect(storeMock).toHaveBeenCalledWith('My playlist', {
        folder_id: folder.id
      }, [])
    })

    it('creates playlist with songs', async () => {
      const songs = factory<Song>('song', 3)
      const folder = factory<PlaylistFolder>('playlist-folder')
      const storeMock = this.mock(playlistStore, 'store').mockResolvedValue(factory<Playlist>('playlist'))

      this.render(CreatePlaylistForm, {
        global: {
          provide: {
            [<symbol>ModalContextKey]: [ref({ folder, songs })]
          }
        }
      })

      screen.getByText('from 3 songs')

      await this.type(screen.getByPlaceholderText('Playlist name'), 'My playlist')
      await this.user.click(screen.getByRole('button', { name: 'Save' }))

      expect(storeMock).toHaveBeenCalledWith('My playlist', {
        folder_id: folder.id
      }, songs)
    })
  }
}
