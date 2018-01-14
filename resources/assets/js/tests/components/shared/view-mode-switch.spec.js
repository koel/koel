import Component from '@/components/shared/view-mode-switch.vue'

describe('components/shared/view-mode-switch', () => {
  it('changes the view mode', () => {
    const wrapper = shallow(Component, { propsData: { 
      mode: 'list',
      for: 'albums'
    }})
    wrapper.find('a.thumbnails').trigger('click')
    wrapper.emitted().viewModeChanged[0].should.eql(['thumbnails'])
    wrapper.find('a.list').trigger('click')
    wrapper.emitted().viewModeChanged[1].should.eql(['list'])
  })
})
