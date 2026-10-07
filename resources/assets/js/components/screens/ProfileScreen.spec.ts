import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { useLocalStorage } from '@/composables/useLocalStorage'
import Component from './ProfileScreen.vue'

describe('profileScreen.vue', () => {
  const h = createHarness({
    beforeEach: () => localStorage.clear(),
  })

  it('opens the profile tab when the remembered tab no longer exists', () => {
    useLocalStorage().set('profileScreenTab', 'a-tab-that-is-gone')

    const { container } = h.render(Component)

    expect(container.querySelector('#profilePaneProfile')?.getAttribute('style') ?? '').not.toContain('display: none')
  })
})
