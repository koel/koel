import Component from '@/components/site-footer/volume.vue'
import { playback, socket } from '@/services'

describe('components/site-footer/volume', () => {
  it('renders properly', () => {
    const wrapper = shallow(Component)
    wrapper.contains('i.mute').should.be.true
    wrapper.contains('#volumeRange').should.be.true
  })
  
  it('mutes', () => {
    const muteStub = sinon.stub(playback, 'mute')
    shallow(Component).find('i.mute').trigger('click')
    muteStub.called.should.be.true 
    muteStub.restore()
  })

  it('unmutes', () => {
    const unmuteStub = sinon.stub(playback, 'unmute')
    shallow(Component, { data: {
      muted: true
    }}).find('i.unmute').trigger('click')
    unmuteStub.called.should.be.true 
    unmuteStub.restore()
  })

  it('sets the volume', () => {
    const setVolumeStub = sinon.stub(playback, 'setVolume')
    const input = shallow(Component).find('#volumeRange')
    input.element.value = 4.2
    input.trigger('input')
    setVolumeStub.calledWith(4.2).should.be.true
    setVolumeStub.restore()
  })

  it('broadcasts the volume value', () => {
    const broadcastStub = sinon.stub(socket, 'broadcast')
    const input = shallow(Component).find('#volumeRange')
    input.element.value = 4.2
    input.trigger('change')
    broadcastStub.calledWith('volume:changed', 4.2).should.be.true
    broadcastStub.restore()
  })
})

