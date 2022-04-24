import Component from '@/components/screens/SettingsScreen.vue'
import { commonStore, settingStore } from '@/stores'
import { alerts } from '@/utils'
import { mock } from '@/__tests__/__helpers__'
import { shallow } from '@/__tests__/adapter'

describe('components/screens/settings', () => {
  beforeEach(() => {
    settingStore.state = {
      settings: {
        media_path: '/foo/'
      }
    }
  })

  afterEach(() => {
    jest.resetModules()
    jest.clearAllMocks()
  })

  it('warns if changing a non-empty media path', () => {
    commonStore.state.originalMediaPath = '/bar'
    const m = mock(alerts, 'confirm')
    shallow(Component).submit('form')
    expect(m).toHaveBeenCalled()
  })

  it("doesn't warn if changing an empty media path", () => {
    commonStore.state.originalMediaPath = ''
    const confirmMock = mock(alerts, 'confirm')
    const updateMock = mock(settingStore, 'update')
    shallow(Component).submit('form')
    expect(confirmMock).not.toHaveBeenCalled()
    expect(updateMock).toHaveBeenCalled()
  })
})
