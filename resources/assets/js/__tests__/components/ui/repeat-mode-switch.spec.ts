import Component from '@/components/ui/repeat-mode-switch.vue'
import { mock } from '@/__tests__/__helpers__'
import { shallow } from '@/__tests__/adapter'
import { playback } from '@/services'

describe('components/ui/repeat-mode-switch', () => {
  afterEach(() => {
    jest.resetModules()
    jest.clearAllMocks()
  })

  it('triggers changing modes', () => {
    const m = mock(playback, 'changeRepeatMode')
    shallow(Component).click('button')
    expect(m).toHaveBeenCalled()
  })
})
