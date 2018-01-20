import Component from '@/components/shared/song-list-controls-toggler.vue'
import isMobile from 'ismobilejs'

describe('components/shared/song-list-controls-toggler', () => {
  beforeEach(() => {
    isMobile.phone = true
  })

  it('renders properly', () => {
    shallow(Component, { propsData: { 
      showingControls: true
    }}).has('.toggler.fa-angle-up').should.be.true

    shallow(Component, { propsData: { 
      showingControls: false
    }}).has('.toggler.fa-angle-up').should.be.false
  })

  it('emits event', () => {
    const wrapper = shallow(Component)
    wrapper.click('.song-list-controls-toggler')
    wrapper.hasEmitted('toggleControls').should.be.true
  })
})
