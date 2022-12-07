import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { screen } from '@testing-library/vue'
import { commonStore } from '@/stores'
import { eventBus } from '@/utils'
import Sidebar from './Sidebar.vue'

const standardItems = [
  'Home',
  'Current Queue',
  'All Songs',
  'Albums',
  'Artists',
  'Genres',
  'Favorites',
  'Recently Played'
]

const adminItems = [...standardItems, 'Users', 'Upload', 'Settings']

new class extends UnitTestCase {
  protected test() {
    it('shows the standard items', () => {
      this.actingAs().render(Sidebar)
      standardItems.forEach(label => screen.getByText(label))
    })

    it('shows administrative items', () => {
      this.actingAsAdmin().render(Sidebar)
      adminItems.forEach(label => screen.getByText(label))
    })

    it('shows the YouTube sidebar item on demand', async () => {
      commonStore.state.use_you_tube = true
      this.render(Sidebar)

      eventBus.emit('PLAY_YOUTUBE_VIDEO', { id: '123', title: 'A Random Video' })
      await this.tick()

      screen.getByText('A Random Video')
    })
  }
}
