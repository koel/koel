import factory from '@/__tests__/factory'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import AlbumTrackList from './AlbumTrackList.vue'
import { songStore } from '@/stores'

new class extends UnitTestCase {
  protected test () {
    it('displays the tracks', async () => {
      const album = factory<Album>('album')
      const fetchMock = this.mock(songStore, 'fetchForAlbum').mockResolvedValue(factory<Song[]>('song', 5))

      const { queryAllByTestId } = this.render(AlbumTrackList, {
        props: {
          album,
          tracks: factory<AlbumTrack[]>('album-track', 3)
        }
      })

      await this.tick()

      expect(fetchMock).toHaveBeenCalledWith(album)
      expect(queryAllByTestId('album-track-item')).toHaveLength(3)
    })
  }
}
