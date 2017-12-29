import Component from '@/components/shared/to-top-button.vue' 
import { $ } from '@/utils'

describe('components/shared/to-top-button', () => {
  it('scrolls to top', () => {
    const scrollToStub = sinon.stub($, 'scrollTo')
    const wrapper = shallow(Component)
    wrapper.find('button').trigger('click')
    scrollToStub.called.should.be.true
    scrollToStub.restore()
  })
})
