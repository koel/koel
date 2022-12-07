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
  private renderComponent() {
    this.render(Sidebar, {
      global: {
        stubs: {
          YouTubeSidebarItem: this.stub('youtube-sidebar-item')
        }
      }
    })
  }

  protected test() {
    it('shows the standard items', () => {
      this.actingAs().renderComponent()
      standardItems.forEach(label => screen.getByText(label))
    })

    it('shows administrative items', () => {
      this.actingAsAdmin().renderComponent()
      adminItems.forEach(label => screen.getByText(label))
    })

    it('shows the YouTube sidebar item on demand', async () => {
      commonStore.state.use_you_tube = true
      this.renderComponent()

      expect(screen.queryByTestId('youtube-sidebar-item')).toBeNull()

      eventBus.emit('PLAY_YOUTUBE_VIDEO', { id: '123', title: 'A Random Video' })
      await this.tick()

      screen.getByTestId('youtube-sidebar-item')
    })
  }
}
