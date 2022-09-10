import { expect, it, vi } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { playlistStore } from '@/stores'
import { PlaylistKey } from '@/symbols'
import { ref } from 'vue'
import { fireEvent, waitFor } from '@testing-library/vue'
import EditPlaylistForm from './EditPlaylistForm.vue'

new class extends UnitTestCase {
  protected test () {
    it('submits', async () => {
      const playlist = factory<Playlist>('playlist', { name: 'My playlist' })
      const updatePlaylistNameMock = vi.fn()
      const updateMock = this.mock(playlistStore, 'update')
      const { getByPlaceholderText, getByRole } = this.render(EditPlaylistForm, {
        global: {
          provide: {
            [<symbol>PlaylistKey]: [ref(playlist), updatePlaylistNameMock]
          }
        }
      })

      await fireEvent.update(getByPlaceholderText('Playlist name'), 'Your playlist')
      await fireEvent.click(getByRole('button', { name: 'Save' }))

      await waitFor(() => {
        expect(updateMock).toHaveBeenCalledWith(playlist, { name: 'Your playlist' })
        expect(updatePlaylistNameMock).toHaveBeenCalledWith('Your playlist')
      })
    })
  }
}
