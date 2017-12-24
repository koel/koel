import Component from '@/components/modals/edit-songs-form.vue'
import factory from '@/tests/factory'

describe('components/modals/edit-songs-form', () => {
  it('opens', async done => {
    const wrapper = shallow(Component)
    await wrapper.vm.open(factory('song', 3))
    wrapper.contains('form').should.be.true
    done()
  })
})
