import Router from '@/router'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { overviewStore } from '@/stores'
import { screen } from '@testing-library/vue'
import RecentlyPlayedSongs from './RecentlyPlayedSongs.vue'

new class extends UnitTestCase {
  protected test () {
    it('displays the songs', () => {
      overviewStore.state.recentlyPlayed = factory('song', 6)
      expect(this.render(RecentlyPlayedSongs, {
        global: {
          stubs: {
            SongCard: this.stub('song-card')
          }
        }
      }).getAllByTestId('song-card')).toHaveLength(6)
    })

    it('goes to dedicated screen', async () => {
      const mock = this.mock(Router, 'go')
      this.render(RecentlyPlayedSongs)

      await this.user.click(screen.getByRole('button', { name: 'View All' }))

      expect(mock).toHaveBeenCalledWith('recently-played')
    })
  }
}
