import { screen } from '@testing-library/vue'
import { afterEach, describe, expect, it } from 'vite-plus/test'
import { defineComponent } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import { Filter } from '@/config/hooks'
import { addFilter, type HookHandle, removeFilter } from '@/hooks'
import Router from '@/router'
import { commonStore } from '@/stores/commonStore'
import Component, { type ProfileTab, type SettingsTab } from './SettingsScreen.vue'

describe('settingsScreen.vue', () => {
  const h = createHarness({
    beforeEach: () => {
      commonStore.state.storage_driver = 'local'
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

  it('opens the section in the URL', () => {
    h.actingAsAdmin()
    h.visit('/settings/services')
    renderComponent()

    screen.getByTestId('services-setting-group')
  })

  it('opens Profile when the section in the URL is not available', () => {
    h.actingAsUser()
    h.visit('/settings/services')
    renderComponent()

    expect(screen.getByTestId('settings-section-profile').getAttribute('aria-selected')).toBe('true')
  })

  it('goes to the URL of the picked section', async () => {
    const goMock = h.mock(Router, 'go')
    h.actingAsAdmin()
    renderComponent()

    await h.user.click(screen.getByTestId('settings-section-media-path'))

    expect(goMock).toHaveBeenCalledWith(Router.url('settings', { section: 'media-path' }))
  })

  it('shows the section under its own heading and labels the panel with it', () => {
    h.actingAsAdmin()
    h.visit('/settings/media-path')
    renderComponent()

    const panel = screen.getByTestId('media-path-setting-group').closest('[role=tabpanel]')
    expect(panel?.getAttribute('aria-labelledby')).toBe(screen.getByTestId('settings-section-media-path').id)
    expect(screen.getByTestId('settings-section-heading').textContent).toBe(
      screen.getByTestId('settings-section-media-path').textContent?.trim(),
    )
  })

  it('puts tabs added through the account settings filter in the account group', () => {
    const ExportTab = defineComponent({ template: '<p data-testid="export-tab-content" />' })
    handle = addFilter<ProfileTab[]>(Filter.ACCOUNT_SETTINGS_TABS, tabs => [
      ...tabs,
      { id: 'export', label: 'Export', component: ExportTab },
    ])
    h.actingAsUser()
    h.visit('/settings/export')
    renderComponent()

    expect(sectionIds()).toEqual([...accountSectionIds, 'export'])
    screen.getByTestId('export-tab-content')
  })

  it('puts tabs added through the server settings filter in the server group', () => {
    const BillingTab = defineComponent({ template: '<p data-testid="billing-tab-content" />' })
    handle = addFilter<SettingsTab[]>(Filter.SERVER_SETTINGS_TABS, tabs => [
      ...tabs,
      { id: 'billing', label: 'Billing', component: BillingTab },
    ])
    h.actingAsAdmin()
    renderComponent()

    expect(sectionIds()).toEqual([...accountSectionIds, 'media-path', 'services', 'billing'])
  })
})
