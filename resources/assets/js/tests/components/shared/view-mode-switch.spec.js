import Component from '@/components/shared/view-mode-switch.vue'

describe('components/shared/view-mode-switch', () => {
  it('changes the view mode', () => {
    const wrapper = shallow(Component, { propsData: { 
      mode: 'list',
      for: 'albums'
    }})
    wrapper.click('a.thumbnails').hasEmitted('viewModeChanged', 'thumbnails').should.be.true
    wrapper.click('a.list').hasEmitted('viewModeChanged', 'list').should.be.true
  })
})
