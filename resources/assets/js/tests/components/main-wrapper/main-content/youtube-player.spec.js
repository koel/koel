import Component from '@/components/main-wrapper/main-content/youtube-player.vue'
import { event } from '@/utils'

describe('components/main-wrapper/main-content/youtube-player', () => {
  it('renders properly', () => {
    mount(Component).find('h1.heading').text().should.contain('YouTube Video')
  })
})
