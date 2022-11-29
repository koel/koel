import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { commonStore, overviewStore } from '@/stores'
import { Events } from '@/config'
import { eventBus } from '@/utils'
import { screen } from '@testing-library/vue'
import HomeScreen from './HomeScreen.vue'

new class extends UnitTestCase {
  private async renderComponent () {
    this.render(HomeScreen)
    await this.router.activateRoute({ path: 'home', screen: 'Home' })
  }

  protected test () {
    it('renders an empty state if no songs found', async () => {
      commonStore.state.song_length = 0
      this.mock(overviewStore, 'init')

      await this.render(HomeScreen)

      screen.getByTestId('screen-empty-state')
    })

    it('renders overview components if applicable', async () => {
      commonStore.state.song_length = 100
      const initMock = this.mock(overviewStore, 'init')

      await this.renderComponent()

      expect(initMock).toHaveBeenCalled()

      ;[
        'most-played-songs',
        'recently-played-songs',
        'recently-added-albums',
        'recently-added-songs',
        'most-played-artists',
        'most-played-albums'
      ].forEach(id => screen.getByTestId(id))

      expect(screen.queryByTestId('screen-empty-state')).toBeNull()
    })

    it.each<[keyof Events]>([['SONGS_UPDATED'], ['SONGS_DELETED']])
    ('refreshes the overviews on %s event', async eventName => {
      const initMock = this.mock(overviewStore, 'init')
      const refreshMock = this.mock(overviewStore, 'refresh')
      await this.renderComponent()

      eventBus.emit(eventName)

      expect(initMock).toHaveBeenCalled()
      expect(refreshMock).toHaveBeenCalled()
    })
  }
}
