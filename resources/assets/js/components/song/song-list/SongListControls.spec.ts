import { screen } from '@testing-library/vue'
import { merge, take } from 'lodash'
import { ref } from 'vue'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { FilteredPlayablesKey, PlayablesKey, SelectedPlayablesKey } from '@/symbols'
import Component from './SongListControls.vue'

new class extends UnitTestCase {
  protected test () {
    it.each([[0], [1]])('shuffles all if %s songs are selected', async (selectedCount: number) => {
      const { emitted } = this.renderComponent(selectedCount)

      await this.user.click(screen.getByTitle('Shuffle all. Press Alt/⌥ to change mode.'))

      expect(emitted()['play-all'][0]).toEqual([true])
    })

    it.each([[0], [1]])('plays all if %s songs are selected with Alt pressed', async (selectedCount: number) => {
      const { emitted } = this.renderComponent(selectedCount)

      await this.user.keyboard('{Alt>}')
      await this.user.click(screen.getByTitle('Play all. Press Alt/⌥ to change mode.'))
      await this.user.keyboard('{/Alt}')

      expect(emitted()['play-all'][0]).toEqual([false])
    })

    it('shuffles selected if more than one song are selected', async () => {
      const { emitted } = this.renderComponent(2)

      await this.user.click(screen.getByTitle('Shuffle selected. Press Alt/⌥ to change mode.'))

      expect(emitted()['play-selected'][0]).toEqual([true])
    })

    it('plays selected if more than one song are selected with Alt pressed', async () => {
      const { emitted } = this.renderComponent(2)

      await this.user.keyboard('{Alt>}')
      await this.user.click(screen.getByTitle('Play selected. Press Alt/⌥ to change mode.'))
      await this.user.keyboard('{/Alt}')

      expect(emitted()['play-selected'][0]).toEqual([false])
    })

    it('clears queue', async () => {
      const { emitted } = this.renderComponent(0)

      await this.user.click(screen.getByTitle('Clear current queue'))

      expect(emitted()['clear-queue']).toBeTruthy()
    })

    it('deletes current playlist', async () => {
      const { emitted } = this.renderComponent(0)

      await this.user.click(screen.getByTitle('Delete this playlist'))

      expect(emitted()['delete-playlist']).toBeTruthy()
    })
  }

  private renderComponent (selectedSongCount = 1, configOverrides: Partial<SongListControlsConfig> = {}) {
    const songs = factory('song', 5)
    const config: SongListControlsConfig = merge({
      addTo: {
        queue: true,
        favorites: true,
      },
      clearQueue: true,
      deletePlaylist: true,
      refresh: true,
      filter: true,
    }, configOverrides)

    return this.render(Component, {
      global: {
        provide: {
          [<symbol>PlayablesKey]: [ref(songs)],
          [<symbol>FilteredPlayablesKey]: [ref(songs)],
          [<symbol>SelectedPlayablesKey]: [ref(take(songs, selectedSongCount))],
        },
      },
      props: {
        config,
      },
    })
  }
}
