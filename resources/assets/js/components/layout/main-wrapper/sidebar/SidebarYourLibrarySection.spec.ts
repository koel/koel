import { screen } from '@testing-library/vue'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { eventBus } from '@/utils/eventBus'
import SidebarYourLibrarySection from './SidebarYourLibrarySection.vue'

new class extends UnitTestCase {
  protected test () {
    it('shows YouTube item if a video is played', async () => {
      this.render(SidebarYourLibrarySection)
      expect(screen.queryByTestId('youtube')).toBeNull()

      eventBus.emit('PLAY_YOUTUBE_VIDEO', {
        id: 'video-id',
        title: 'Another One Bites the Dust',
      })

      await this.tick()
      screen.getByTestId('youtube')
      screen.getByText('Another One Bites the Dust')
    })
  }
}
