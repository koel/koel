import Component from '@/components/site-header/search-form.vue'
import { event } from '@/utils'

describe('components/site-header/search-form', () => {
  it('renders properly', () => {
    shallow(Component).has('[type=search]').should.be.true
  })

  it('emits an event to filter', async done => { 
    const emitStub = sinon.stub(event, 'emit')
    const wrapper = shallow(Component)
    wrapper.find('[type=search]').setValue('foo').input()
    setTimeout(() => {
      emitStub.calledWith('filter:changed', 'foo').should.be.true
      emitStub.restore()
      done()
    }, 200)
  })
})
