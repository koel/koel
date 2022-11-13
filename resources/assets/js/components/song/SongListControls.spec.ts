import { take } from 'lodash'
import { ref } from 'vue'
import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import { fireEvent } from '@testing-library/vue'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { SelectedSongsKey, SongsKey } from '@/symbols'
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
      const { emitted, getByTitle } = this.renderComponent(selectedCount)

      await fireEvent.click(getByTitle('Shuffle all songs'))

      expect(emitted().playAll[0]).toEqual([true])
    })

    it.each([[0], [1]])('plays all if %s songs are selected with Alt pressed', async (selectedCount: number) => {
      const { emitted, getByTitle } = this.renderComponent(selectedCount)

      await fireEvent.keyDown(window, { key: 'Alt' })
      await fireEvent.click(getByTitle('Play all songs'))

      expect(emitted().playAll[0]).toEqual([false])
    })

    it('shuffles selected if more than one song are selected', async () => {
      const { emitted, getByTitle } = this.renderComponent(2)

      await fireEvent.click(getByTitle('Shuffle selected songs'))

      expect(emitted().playSelected[0]).toEqual([true])
    })

    it('plays selected if more than one song are selected with Alt pressed', async () => {
      const { emitted, getByTitle } = this.renderComponent(2)

      await fireEvent.keyDown(window, { key: 'Alt' })
      await fireEvent.click(getByTitle('Play selected songs'))

      expect(emitted().playSelected[0]).toEqual([false])
    })

    it('clears queue', async () => {
      const { emitted, getByTitle } = this.renderComponent(0, { clearQueue: true })

      await fireEvent.click(getByTitle('Clear current queue'))

      expect(emitted().clearQueue).toBeTruthy()
    })

    it('deletes current playlist', async () => {
      const { emitted, getByTitle } = this.renderComponent(0, { deletePlaylist: true })

      await fireEvent.click(getByTitle('Delete this playlist'))

      expect(emitted().deletePlaylist).toBeTruthy()
    })
  }
}
