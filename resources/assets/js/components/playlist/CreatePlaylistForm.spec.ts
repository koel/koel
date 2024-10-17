import { expect, it } from 'vitest'
import { ref } from 'vue'
import { screen } from '@testing-library/vue'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { playlistStore } from '@/stores/playlistStore'
import { ModalContextKey } from '@/symbols'
import Component from './CreatePlaylistForm.vue'

new class extends UnitTestCase {
  protected test () {
    it('creates playlist with no playables', async () => {
      const folder = factory('playlist-folder')
      const storeMock = this.mock(playlistStore, 'store').mockResolvedValue(factory('playlist'))

      this.render(Component, {
        global: {
          provide: {
            [<symbol>ModalContextKey]: [ref({ folder })],
          },
        },
      })

      expect(screen.queryByTestId('from-playables')).toBeNull()

      await this.type(screen.getByPlaceholderText('Playlist name'), 'My playlist')
      await this.user.click(screen.getByRole('button', { name: 'Save' }))

      expect(storeMock).toHaveBeenCalledWith('My playlist', {
        folder_id: folder.id,
      }, [])
    })

    it('creates playlist with playables', async () => {
      const playables = factory('song', 3)
      const folder = factory('playlist-folder')
      const storeMock = this.mock(playlistStore, 'store').mockResolvedValue(factory('playlist'))

      this.render(Component, {
        global: {
          provide: {
            [<symbol>ModalContextKey]: [ref({ folder, playables })],
          },
        },
      })

      screen.getByText('from 3 songs')

      await this.type(screen.getByPlaceholderText('Playlist name'), 'My playlist')
      await this.user.click(screen.getByRole('button', { name: 'Save' }))

      expect(storeMock).toHaveBeenCalledWith('My playlist', {
        folder_id: folder.id,
      }, playables)
    })
  }
}
