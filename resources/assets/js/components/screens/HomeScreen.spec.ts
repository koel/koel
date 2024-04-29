import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { commonStore, overviewStore } from '@/stores'
import { Events } from '@/config'
import { eventBus } from '@/utils'
import { screen } from '@testing-library/vue'
import HomeScreen from './HomeScreen.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders an empty state if no songs found', async () => {
      commonStore.state.song_length = 0
      this.mock(overviewStore, 'fetch')

      this.render(HomeScreen)

      screen.getByTestId('screen-empty-state')
    })

    it('renders overview components if applicable', async () => {
      commonStore.state.song_length = 100
      const fetchOverviewMock = this.mock(overviewStore, 'fetch')

      await this.renderComponent()

      expect(fetchOverviewMock).toHaveBeenCalled()

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

    it.each<[keyof Events]>([['SONGS_UPDATED'], ['SONGS_DELETED'], ['SONG_UPLOADED']])
    ('refreshes the overviews on %s event', async eventName => {
      const fetchOverviewMock = this.mock(overviewStore, 'fetch')
      await this.renderComponent()

      eventBus.emit(eventName)

      expect(fetchOverviewMock).toHaveBeenCalled()
    })
  }

  private async renderComponent () {
    this.render(HomeScreen)
    await this.router.activateRoute({ path: 'home', screen: 'Home' })
  }
}
