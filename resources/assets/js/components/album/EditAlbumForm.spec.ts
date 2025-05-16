import { ref } from 'vue'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { screen, waitFor } from '@testing-library/vue'
import { ModalContextKey } from '@/symbols'
import { albumStore } from '@/stores/albumStore'
import Component from './EditAlbumForm.vue'

new class extends UnitTestCase {
  protected test () {
    it('submits', async () => {
      const album = factory('album', {
        name: 'A Real Good One',
        year: 2023,
      })

      albumStore.state.albums = [album]

      const updateMock = this.mock(albumStore, 'update')

      this.render(Component, {
        global: {
          provide: {
            [<symbol>ModalContextKey]: [ref({ album })],
          },
        },
      })

      await this.type(screen.getByTitle('Album name'), 'Not So Good Actually')
      await this.type(screen.getByTitle('Release year'), '2022')
      await this.user.click(screen.getByRole('button', { name: 'Save' }))

      await waitFor(() => {
        expect(updateMock).toHaveBeenCalledWith(album, {
          name: 'Not So Good Actually',
          year: 2022,
        })
      })
    })
  }
}
