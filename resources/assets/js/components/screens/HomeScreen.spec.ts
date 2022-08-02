import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import HomeScreen from './HomeScreen.vue'
import { commonStore } from '@/stores'

new class extends UnitTestCase {
  protected test () {
    it('renders an empty state if no songs found', () => {
      commonStore.state.song_length = 0
      this.render(HomeScreen).getByTestId('screen-empty-state')
    })

    it('renders overview components if applicable', () => {
      commonStore.state.song_length = 100
      const { getByTestId, queryByTestId } = this.render(HomeScreen)

      ;[
        'most-played-songs',
        'recently-played-songs',
        'recently-added-albums',
        'recently-added-songs',
        'most-played-artists',
        'most-played-albums'
      ].forEach(getByTestId)

      expect(queryByTestId('screen-empty-state')).toBeNull()
    })
  }
}
