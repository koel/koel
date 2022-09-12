import { ref } from 'vue'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { commonStore } from '@/stores'
import { ActiveScreenKey } from '@/symbols'
import HomeScreen from './HomeScreen.vue'

new class extends UnitTestCase {
  private renderComponent () {
    return this.render(HomeScreen, {
      global: {
        provide: {
          [<symbol>ActiveScreenKey]: ref('Home')
        }
      }
    })
  }

  protected test () {
    it('renders an empty state if no songs found', () => {
      commonStore.state.song_length = 0
      this.renderComponent().getByTestId('screen-empty-state')
    })

    it('renders overview components if applicable', () => {
      commonStore.state.song_length = 100

      const { getByTestId, queryByTestId } = this.renderComponent()

      ;[
        'most-played-songs',
        'recently-played-songs',
        'recently-added-albums',
        'recently-added-songs',
        'most-played-artists',
        'most-played-albums'
      ].forEach(id => getByTestId(id))

      expect(queryByTestId('screen-empty-state')).toBeNull()
    })
  }
}
