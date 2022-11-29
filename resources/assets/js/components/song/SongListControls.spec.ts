import { take } from 'lodash'
import { ref } from 'vue'
import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { SelectedSongsKey, SongsKey } from '@/symbols'
import { screen } from '@testing-library/vue'
import SongListControls from './SongListControls.vue'

new class extends UnitTestCase {
  private renderComponent (selectedSongCount = 1, config: Partial<SongListControlsConfig> = {}) {
    const songs = factory<Song>('song', 5)

    return this.render(SongListControls, {
      props: {
        config
      },
      global: {
        provide: {
          [<symbol>SongsKey]: [ref(songs)],
          [<symbol>SelectedSongsKey]: [ref(take(songs, selectedSongCount))]
        }
      }
    })
  }

  protected test () {
    it.each([[0], [1]])('shuffles all if %s songs are selected', async (selectedCount: number) => {
      const { emitted } = this.renderComponent(selectedCount)

      await this.user.click(screen.getByTitle('Shuffle all songs'))

      expect(emitted().playAll[0]).toEqual([true])
    })

    it.each([[0], [1]])('plays all if %s songs are selected with Alt pressed', async (selectedCount: number) => {
      const { emitted } = this.renderComponent(selectedCount)

      await this.user.keyboard('{Alt>}')
      await this.user.click(screen.getByTitle('Play all songs'))
      await this.user.keyboard('{/Alt}')

      expect(emitted().playAll[0]).toEqual([false])
    })

    it('shuffles selected if more than one song are selected', async () => {
      const { emitted } = this.renderComponent(2)

      await this.user.click(screen.getByTitle('Shuffle selected songs'))

      expect(emitted().playSelected[0]).toEqual([true])
    })

    it('plays selected if more than one song are selected with Alt pressed', async () => {
      const { emitted } = this.renderComponent(2)

      await this.user.keyboard('{Alt>}')
      await this.user.click(screen.getByTitle('Play selected songs'))
      await this.user.keyboard('{/Alt}')

      expect(emitted().playSelected[0]).toEqual([false])
    })

    it('clears queue', async () => {
      const { emitted } = this.renderComponent(0, { clearQueue: true })

      await this.user.click(screen.getByTitle('Clear current queue'))

      expect(emitted().clearQueue).toBeTruthy()
    })

    it('deletes current playlist', async () => {
      const { emitted } = this.renderComponent(0, { deletePlaylist: true })

      await this.user.click(screen.getByTitle('Delete this playlist'))

      expect(emitted().deletePlaylist).toBeTruthy()
    })
  }
}
