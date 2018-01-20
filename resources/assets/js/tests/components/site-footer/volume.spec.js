import Component from '@/components/site-footer/volume.vue'
import { playback, socket } from '@/services'

describe('components/site-footer/volume', () => {
  it('renders properly', () => {
    const wrapper = shallow(Component)
    wrapper.hasAll('i.mute', '#volumeRange').should.be.true
  })
  
  it('mutes', () => {
    const muteStub = sinon.stub(playback, 'mute')
    shallow(Component).click('i.mute')
    muteStub.called.should.be.true 
    muteStub.restore()
  })

  it('unmutes', () => {
    const unmuteStub = sinon.stub(playback, 'unmute')
    shallow(Component, { data: {
      muted: true
    }}).click('i.unmute')
    unmuteStub.called.should.be.true 
    unmuteStub.restore()
  })

  it('sets the volume', () => {
    const setVolumeStub = sinon.stub(playback, 'setVolume')
    shallow(Component).find('#volumeRange').setValue(4.2).input()
    setVolumeStub.calledWith(4.2).should.be.true
    setVolumeStub.restore()
  })

  it('broadcasts the volume value', () => {
    const broadcastStub = sinon.stub(socket, 'broadcast')
    shallow(Component).find('#volumeRange').setValue(4.2).change()
    broadcastStub.calledWith('volume:changed', 4.2).should.be.true
    broadcastStub.restore()
  })
})

