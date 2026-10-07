import { afterEach, describe, expect, it } from 'vite-plus/test'
import { defineComponent } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import { useLocalStorage } from '@/composables/useLocalStorage'
import { Filter } from '@/config/hooks'
import { addFilter, type HookHandle, removeFilter } from '@/hooks'
import Component, { type ProfileTab } from './ProfileScreen.vue'

describe('profileScreen.vue', () => {
  const h = createHarness({
    beforeEach: () => localStorage.clear(),
  })

  it('opens the profile tab when the remembered tab no longer exists', () => {
    useLocalStorage().set('profileScreenTab', 'a-tab-that-is-gone')

    const { container } = h.render(Component)

    expect(container.querySelector('#profilePaneProfile')?.getAttribute('style') ?? '').not.toContain('display: none')
  })

  let handle: HookHandle | null = null

  afterEach(() => {
    if (handle) {
      removeFilter(handle)
      handle = null
    }
  })

  it('reopens a remembered tab that was added through the filter', () => {
    const ExportTab = defineComponent({ template: '<p data-testid="export-tab-content" />' })
    handle = addFilter<ProfileTab[]>(Filter.PROFILE_TABS, tabs => [
      ...tabs,
      { id: 'export', label: 'Export', component: ExportTab },
    ])
    useLocalStorage().set('profileScreenTab', 'export')

    const { getByTestId } = h.render(Component)

    expect(getByTestId('export-tab-content')).toBeTruthy()
  })
})
