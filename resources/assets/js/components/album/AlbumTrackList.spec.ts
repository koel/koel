import factory from '@/__tests__/factory'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import AlbumTrackList from './AlbumTrackList.vue'
import TrackListItem from './AlbumTrackListItem.vue'

new class extends UnitTestCase {
  protected test () {
    it('lists the correct number of tracks', () => {
      const { queryAllByTestId } = this.render(AlbumTrackList, {
        props: {
          album: factory<Album>('album')
        },
        global: {
          stubs: {
            TrackListItem
          }
        }
      })

      expect(queryAllByTestId('album-track-item')).toHaveLength(2)
    })
  }
}
