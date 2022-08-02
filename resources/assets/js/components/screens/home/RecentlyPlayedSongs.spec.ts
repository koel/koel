import { expect, it } from 'vitest'
import { recentlyPlayedStore } from '@/stores'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import RecentlyPlayedSongs from './RecentlyPlayedSongs.vue'
import { fireEvent } from '@testing-library/vue'
import router from '@/router'

new class extends UnitTestCase {
  protected test () {
    it('displays the songs', () => {
      recentlyPlayedStore.excerptState.songs = factory<Song[]>('song', 6)
      expect(this.render(RecentlyPlayedSongs).getAllByTestId('song-card')).toHaveLength(6)
    })

    it('goes to dedicated screen', async () => {
      const mock = this.mock(router, 'go')
      const { getByTestId } = this.render(RecentlyPlayedSongs)

      await fireEvent.click(getByTestId('home-view-all-recently-played-btn'))

      expect(mock).toHaveBeenCalledWith('recently-played')
    })
  }
}
