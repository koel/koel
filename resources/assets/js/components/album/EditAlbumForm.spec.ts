import { ref } from 'vue'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { screen, waitFor } from '@testing-library/vue'
import { ModalContextKey } from '@/symbols'
import { albumStore } from '@/stores/albumStore'
import Component from './EditAlbumForm.vue'

describe('editAlbumForm.vue', () => {
  const h = createHarness()

  it('submits', async () => {
    const album = h.factory('album', {
      name: 'A Real Good One',
      year: 2023,
    })

    albumStore.state.albums = [album]

    const updateMock = h.mock(albumStore, 'update')

    h.render(Component, {
      global: {
        provide: {
          [<symbol>ModalContextKey]: [ref({ album })],
        },
      },
    })

    await h.type(screen.getByTitle('Album name'), 'Not So Good Actually')
    await h.type(screen.getByTitle('Release year'), '2022')
    await h.user.click(screen.getByRole('button', { name: 'Save' }))

    await waitFor(() => {
      expect(updateMock).toHaveBeenCalledWith(album, {
        name: 'Not So Good Actually',
        year: 2022,
      })
    })
  })
})
