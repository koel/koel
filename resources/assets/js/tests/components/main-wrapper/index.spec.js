import Component from '@/components/main-wrapper/index.vue'
import Sidebar from '@/components/main-wrapper/sidebar/index.vue'
import MainContent from '@/components/main-wrapper/main-content/index.vue'
import Extra from '@/components/main-wrapper/extra/index.vue'

describe('component/main-wrapper/index', () => {
  it('renders properly', () => {
    const wrapper = mount(Component)
    wrapper.contains(Sidebar).should.be.true
    wrapper.contains(MainContent).should.be.true
    wrapper.contains(Extra).should.be.true
  })
})
