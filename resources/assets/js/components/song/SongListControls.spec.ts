import { take } from 'lodash'
import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import { fireEvent } from '@testing-library/vue'
import ComponentTestCase from '@/__tests__/ComponentTestCase'
import SongListControls from './SongListControls.vue'
import AddToMenu from '@/components/song/AddToMenu.vue'
import Btn from '@/components/ui/Btn.vue'
import BtnGroup from '@/components/ui/BtnGroup.vue'

new class extends ComponentTestCase {
  private renderComponent (selectedSongCount = 1, config: Partial<SongListControlsConfig> = {}) {
    const songs = factory<Song>('song', 5)

    return this.render(SongListControls, {
      props: {
        config,
        songs,
        selectedSongs: take(songs, selectedSongCount)
      },
      global: {
        stubs: {
          AddToMenu,
          Btn,
          BtnGroup
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

    it('toggles Add To menu', async () => {
      const { getByTitle, getByTestId } = this.renderComponent()

      await fireEvent.click(getByTitle('Add selected songs to…'))
      expect(getByTestId('add-to-menu').style.display).toBe('')

      await fireEvent.click(getByTitle('Cancel'))
      expect(getByTestId('add-to-menu').style.display).toBe('none')
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
