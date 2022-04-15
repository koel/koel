import Component from '@/components/layout/app-header.vue'
import compareVersions from 'compare-versions'
import { eventBus } from '@/utils'
import { mock } from '@/__tests__/__helpers__'
import { shallow } from '@/__tests__/adapter'
import { sharedStore, userStore } from '@/stores'
import factory from '@/__tests__/factory'

describe('components/layout/app-header', () => {
  afterEach(() => {
    jest.resetModules()
    jest.clearAllMocks()
  })

  it('toggles sidebar', () => {
    const m = mock(eventBus, 'emit')
    shallow(Component).click('.hamburger')
    expect(m).toHaveBeenCalledWith('TOGGLE_SIDEBAR')
  })

  it('toggles search form', () => {
    const m = mock(eventBus, 'emit')
    shallow(Component).click('.magnifier')
    expect(m).toHaveBeenCalledWith('TOGGLE_SEARCH_FORM')
  })

  it.each([[true, true, true], [false, true, false], [true, false, false], [false, false, false]])(
    'announces a new version if applicable',
    (hasNewVersion, isAdmin, shouldAnnounce) => {
      mock(compareVersions, 'compare').mockReturnValue(hasNewVersion)
      userStore.state.current = factory<User>('user', {
        is_admin: isAdmin
      })
      const wrapper = shallow(Component)
      expect(wrapper.has('[data-test=new-version-available]')).toBe(shouldAnnounce)
    }
  )
})
