import Component from '@/components/ui/volume.vue'
import { playback, socket } from '@/services'
import { mock } from '@/__tests__/__helpers__'
import { shallow } from '@/__tests__/adapter'

describe('components/ui/volume', () => {
  afterEach(() => {
    jest.resetModules()
    jest.clearAllMocks()
  })

  it('renders properly', () => {
    expect(shallow(Component)).toMatchSnapshot()
  })

  it('mutes', () => {
    const m = mock(playback, 'mute')
    shallow(Component).click('i.mute')
    expect(m).toHaveBeenCalled()
  })

  it('unmutes', () => {
    const m = mock(playback, 'unmute')
    shallow(Component, {
      data: () => ({
        muted: true
      })
    }).click('i.unmute')
    expect(m).toHaveBeenCalled()
  })

  it('sets the volume', () => {
    const m = mock(playback, 'setVolume')
    shallow(Component).find('#volumeRange').setValue('4.2').input()
    expect(m).toHaveBeenCalledWith(4.2)
  })

  it('broadcasts the volume value', () => {
    const m = mock(socket, 'broadcast')
    shallow(Component).find('#volumeRange').setValue('4.2').change()
    expect(m).toHaveBeenCalledWith('SOCKET_VOLUME_CHANGED', 4.2)
  })
})
