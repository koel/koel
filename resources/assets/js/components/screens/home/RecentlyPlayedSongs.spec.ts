import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { fireEvent } from '@testing-library/vue'
import { overviewStore } from '@/stores'
import RecentlyPlayedSongs from './RecentlyPlayedSongs.vue'

new class extends UnitTestCase {
  protected test () {
    it('displays the songs', () => {
      overviewStore.state.recentlyPlayed = factory<Song>('song', 6)
      expect(this.render(RecentlyPlayedSongs).getAllByTestId('song-card')).toHaveLength(6)
    })

    it('goes to dedicated screen', async () => {
      const mock = this.mock(this.router, 'go')
      const { getByTestId } = this.render(RecentlyPlayedSongs)

      await fireEvent.click(getByTestId('home-view-all-recently-played-btn'))

      expect(mock).toHaveBeenCalledWith('recently-played')
    })
  }
}
