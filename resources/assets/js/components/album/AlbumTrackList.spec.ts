import { screen } from '@testing-library/vue'
import factory from '@/__tests__/factory'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import AlbumTrackList from './AlbumTrackList.vue'
import { songStore } from '@/stores'

new class extends UnitTestCase {
  protected test () {
    it('displays the tracks', async () => {
      const album = factory('album')
      const fetchMock = this.mock(songStore, 'fetchForAlbum').mockResolvedValue(factory('song', 5))

      this.render(AlbumTrackList, {
        props: {
          album,
          tracks: factory('album-track', 3)
        }
      })

      await this.tick()

      expect(fetchMock).toHaveBeenCalledWith(album)
      expect(screen.queryAllByTestId('album-track-item')).toHaveLength(3)
    })
  }
}
