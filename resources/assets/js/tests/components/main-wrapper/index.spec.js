import Component from '@/components/main-wrapper/index.vue'
import Sidebar from '@/components/main-wrapper/sidebar/index.vue'
import MainContent from '@/components/main-wrapper/main-content/index.vue'
import Extra from '@/components/main-wrapper/extra/index.vue'

describe('component/main-wrapper/index', () => {
  it('renders properly', () => {
    mount(Component).hasAll(Sidebar, MainContent, Extra).should.be.true
  })
})
