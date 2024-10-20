import { it } from 'vitest'
import { screen } from '@testing-library/vue'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { commonStore } from '@/stores/commonStore'
import { eventBus } from '@/utils/eventBus'
import Sidebar from './Sidebar.vue'

const standardItems = [
  'All Songs',
  'Albums',
  'Artists',
  'Genres',
  'Favorites',
  'Recently Played',
]

const adminItems = [...standardItems, 'Users', 'Upload', 'Settings']

new class extends UnitTestCase {
  protected test () {
    it('shows the standard items', () => {
      this.be().render(Sidebar)
      standardItems.forEach(label => screen.getByText(label))
    })

    it('shows administrative items', () => {
      this.beAdmin().render(Sidebar)
      adminItems.forEach(label => screen.getByText(label))
    })

    it('shows the YouTube sidebar item on demand', async () => {
      commonStore.state.uses_you_tube = true
      this.render(Sidebar)

      eventBus.emit('PLAY_YOUTUBE_VIDEO', { id: '123', title: 'A Random Video' })
      await this.tick()

      screen.getByText('A Random Video')
    })
  }
}
