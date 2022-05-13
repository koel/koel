import { without } from 'lodash'
import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import { queueStore } from '@/stores'
import { playbackService } from '@/services'
import { fireEvent } from '@testing-library/vue'
import SongListItem from './SongListItem.vue'
import UnitTestCase from '@/__tests__/UnitTestCase'

let row: SongRow

new class extends UnitTestCase {
  private renderComponent (columns: SongListColumn[] = ['track', 'title', 'artist', 'album', 'length']) {
    row = {
      song: factory<Song>('song'),
      selected: false
    }

    return this.render(SongListItem, {
      props: {
        item: row,
        columns
      }
    })
  }

  protected test () {
    it('plays on double click', async () => {
      const queueMock = this.mock(queueStore, 'queueIfNotQueued')
      const playMock = this.mock(playbackService, 'play')
      const { getByTestId } = this.renderComponent()

      await fireEvent.dblClick(getByTestId('song-item'))

      expect(queueMock).toHaveBeenCalledWith(row.song)
      expect(playMock).toHaveBeenCalledWith(row.song)
    })

    it.each<[SongListColumn, string]>([
      ['track', '.track-number'],
      ['title', '.title'],
      ['artist', '.artist'],
      ['album', '.album'],
      ['length', '.time']
    ])('does not render %s if so configure', async (column: SongListColumn, selector: string) => {
      const { container } = this.renderComponent(without(['track', 'title', 'artist', 'album', 'length'], column))
      expect(container.querySelector(selector)).toBeNull()
    })
  }
}
