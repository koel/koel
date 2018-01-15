import Component from '@/components/site-header/search-form.vue'
import { event } from '@/utils'

describe('components/site-header/search-form', () => {
  it('renders properly', () => {
    shallow(Component).contains('[type=search]').should.be.true
  })

  it('emits an event to filter', async done => { 
    const emitStub = sinon.stub(event, 'emit')
    const wrapper = shallow(Component)
    const input = wrapper.find('[type=search]')
    input.element.value = 'foo'
    input.trigger('input')
    setTimeout(() => {
      emitStub.calledWith('filter:changed', 'foo').should.be.true
      emitStub.restore()
      done()
    }, 200)
  })
})
