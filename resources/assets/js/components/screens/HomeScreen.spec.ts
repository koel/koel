import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { commonStore, overviewStore } from '@/stores'
import { EventName } from '@/config'
import { eventBus } from '@/utils'
import HomeScreen from './HomeScreen.vue'

new class extends UnitTestCase {
  private async renderComponent () {
    const rendered = this.render(HomeScreen)
    await this.router.activateRoute({ path: 'home', screen: 'Home' })
    return rendered
  }

  protected test () {
    it('renders an empty state if no songs found', async () => {
      commonStore.state.song_length = 0
      const { getByTestId } = await this.render(HomeScreen)
      getByTestId('screen-empty-state')
    })

    it('renders overview components if applicable', async () => {
      commonStore.state.song_length = 100
      const initMock = this.mock(overviewStore, 'init')

      const { getByTestId, queryByTestId } = await this.renderComponent()

      expect(initMock).toHaveBeenCalled()

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

    it.each<[EventName]>([['SONGS_UPDATED'], ['SONGS_DELETED']])
    ('refreshes the overviews on %s event', async (eventName) => {
      const initMock = this.mock(overviewStore, 'init')
      const refreshMock = this.mock(overviewStore, 'refresh')
      await this.renderComponent()

      eventBus.emit(eventName)

      expect(initMock).toHaveBeenCalled()
      expect(refreshMock).toHaveBeenCalled()
    })
  }
}
