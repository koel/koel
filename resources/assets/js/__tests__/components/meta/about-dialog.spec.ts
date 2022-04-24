import Component from '@/components/meta/AboutKoelModal.vue'
import factory from '@/__tests__/factory'
import { shallow } from '@/__tests__/adapter'

describe('components/meta/AboutKoelModal', () => {
  const versionPermutations = [
    ['v4.0.0'/* latest ver */, 'v4.0.0-beta'/* this ver */, true/* admin */, true/* show new ver notification */],
    ['v4.0.0', 'v4.0.0', true, false],
    ['v4.0.0', 'v3.9.0', false, false]
  ]

  it.each(versionPermutations)('new version notification', (latestVersion, currentVersion, isAdmin, visible) => {
    const wrapper = shallow(Component, {
      data: () => ({
        userState: {
          current: factory('user', {
            is_admin: isAdmin
          })
        },
        sharedState: {
          latestVersion,
          currentVersion
        }
      })
    })
    expect(wrapper.has('.new-version')).toBe(visible)
  })

  const demoPermutations = [
    [true, true],
    [false, false]
  ]

  it.each(demoPermutations)('builds demo version with(out) credits', (inDemoEnv, creditVisible) => {
    const wrapper = shallow(Component, {
      data: () => ({
        userState: {
          current: factory('user', {
            is_admin: true
          })
        },
        sharedState: {
          latestVersion: 'v1.0.0',
          currentVersion: 'v1.0.0'
        },
        demo: inDemoEnv
      })
    })
    expect(wrapper.has('.demo-credits')).toBe(creditVisible)
  })
})
