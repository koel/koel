import Component from '@/components/main-wrapper/main-content/settings.vue'
import { sharedStore, settingStore } from '@/stores'
import { alerts } from '@/utils'

describe('components/main-wrapper/main-content/settings', () => {
  beforeEach(() => {
    settingStore.state = {
      settings: {
        media_path: '/foo/'
      }
    }
  })

  it('renders a settings form', () => {
    const wrapper = shallow(Component)
    wrapper.findAll('form').should.have.lengthOf(1)
  })

  it('warns if changing a non-empty media path', () => {
    sharedStore.state.originalMediaPath = '/bar'
    const wrapper = shallow(Component)
    const stub = sinon.stub(alerts, 'confirm')
    wrapper.find('form').trigger('submit')
    stub.called.should.be.true
    stub.restore()
  })

  it("doesn't warn if changing an empty media path", () => {
    sharedStore.state.originalMediaPath = ''
    const wrapper = shallow(Component)
    const confirmStub = sinon.stub(alerts, 'confirm')
    const updateStub = sinon.stub(settingStore, 'update')
    wrapper.find('form').trigger('submit')
    confirmStub.called.should.be.false
    updateStub.called.should.be.true
    confirmStub.restore()
    updateStub.restore()
  })
})
