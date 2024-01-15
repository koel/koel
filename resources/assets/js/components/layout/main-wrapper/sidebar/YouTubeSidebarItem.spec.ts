import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { eventBus } from '@/utils'
import YouTubeSidebarItem from './YouTubeSidebarItem.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders', async () => {
      const { html } = this.render(YouTubeSidebarItem)

      eventBus.emit('PLAY_YOUTUBE_VIDEO', { id: '123', title: 'A Random Video' })
      await this.tick()

      expect(html()).toMatchSnapshot()
    })
  }
}
