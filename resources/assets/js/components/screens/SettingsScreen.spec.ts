import { screen } from '@testing-library/vue'
import { afterEach, describe, expect, it } from 'vite-plus/test'
import { defineComponent } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import { useLocalStorage } from '@/composables/useLocalStorage'
import { Filter } from '@/config/hooks'
import { addFilter, type HookHandle, removeFilter } from '@/hooks'
import { commonStore } from '@/stores/commonStore'
import Component, { type ProfileTab, type SettingsTab } from './SettingsScreen.vue'

describe('settingsScreen.vue', () => {
  const h = createHarness({
    beforeEach: () => {
      commonStore.state.storage_driver = 'local'
      localStorage.clear()
    },
  })

  let handle: HookHandle | null = null

  afterEach(() => {
    if (handle) {
      removeFilter(handle)
      handle = null
    }
  })

  const renderComponent = () =>
    h.render(Component, {
      global: {
        stubs: {
          MediaPathSettingGroup: h.stub('media-path-setting-group'),
          BrandingSettingGroup: h.stub('branding-setting-group'),
          AiSettingGroup: h.stub('ai-setting-group'),
          ServicesSettingGroup: h.stub('services-setting-group'),
        },
      },
    })

  const sectionIds = () =>
    screen.getAllByRole('tab', { hidden: true }).map(tab => tab.dataset.testid?.replace('settings-section-', ''))

  const accountSectionIds = [
    'profile',
    'preferences',
    'themes',
    'integrations',
    'offline',
    'subsonic',
    'security',
    'qr',
  ]

  it('shows only the account sections to a regular user', () => {
    h.actingAsUser()
    renderComponent()

    expect(sectionIds()).toEqual(accountSectionIds)
  })

  it('adds the media path and services sections for admins in the Community edition', () => {
    h.actingAsAdmin()
    renderComponent()

    expect(sectionIds()).toEqual([...accountSectionIds, 'media-path', 'services'])
  })

  it('adds the branding and AI sections for admins in the Plus edition', async () => {
    h.actingAsAdmin()

    await h.withPlusEdition(() => {
      renderComponent()

      expect(sectionIds()).toEqual([...accountSectionIds, 'media-path', 'branding', 'ai', 'services'])
    })
  })

  it('leaves out the media path section when the storage is not local', () => {
    commonStore.state.storage_driver = 's3'
    h.actingAsAdmin()
    renderComponent()

    expect(sectionIds()).not.toContain('media-path')
  })

  it('opens the remembered section', () => {
    h.actingAsAdmin()
    useLocalStorage().set('settingsSection', 'services')
    renderComponent()

    screen.getByTestId('services-setting-group')
  })

  it('opens Profile when the remembered section is not available', () => {
    h.actingAsUser()
    useLocalStorage().set('settingsSection', 'services')
    renderComponent()

    expect(screen.getByTestId('settings-section-profile').getAttribute('aria-selected')).toBe('true')
  })

  it('shows the picked section and labels the panel with it', async () => {
    h.actingAsAdmin()
    renderComponent()

    await h.user.click(screen.getByTestId('settings-section-media-path'))

    const panel = screen.getByTestId('media-path-setting-group').closest('[role=tabpanel]')
    expect(panel?.getAttribute('aria-labelledby')).toBe(screen.getByTestId('settings-section-media-path').id)
  })

  it('puts sections added through the profile tabs filter in the account group', () => {
    const ExportTab = defineComponent({ template: '<p data-testid="export-tab-content" />' })
    handle = addFilter<ProfileTab[]>(Filter.PROFILE_TABS, tabs => [
      ...tabs,
      { id: 'export', label: 'Export', component: ExportTab },
    ])
    h.actingAsUser()
    useLocalStorage().set('settingsSection', 'export')
    renderComponent()

    expect(sectionIds()).toEqual([...accountSectionIds, 'export'])
    screen.getByTestId('export-tab-content')
  })

  it('puts sections added through the settings tabs filter in the server group', () => {
    const BillingTab = defineComponent({ template: '<p data-testid="billing-tab-content" />' })
    handle = addFilter<SettingsTab[]>(Filter.SETTINGS_TABS, tabs => [
      ...tabs,
      { id: 'billing', label: 'Billing', component: BillingTab },
    ])
    h.actingAsAdmin()
    renderComponent()

    expect(sectionIds()).toEqual([...accountSectionIds, 'media-path', 'services', 'billing'])
  })
})
