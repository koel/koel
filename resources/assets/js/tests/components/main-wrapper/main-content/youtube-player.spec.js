import Component from '@/components/main-wrapper/main-content/youtube-player.vue'
import { event } from '@/utils'

describe('components/main-wrapper/main-content/youtube-player', () => {
  it('renders properly', () => {
    const wrapper = mount(Component)
    wrapper.find('h1.heading').text().should.contain('YouTube Video')
  })
})
