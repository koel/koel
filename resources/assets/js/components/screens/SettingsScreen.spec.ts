import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { commonStore } from '@/stores/commonStore'
import Component from './SettingsScreen.vue'

describe('settingsScreen.vue', () => {
  const h = createHarness({
    beforeEach: () => {
      commonStore.state.storage_driver = 'local'
    },
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

  const tabIds = () => screen.getAllByTestId(/^settings-tab-/).map(tab => tab.dataset.testid)

  it('offers the library and services tabs in the Community edition', () => {
    renderComponent()

    expect(tabIds()).toEqual(['settings-tab-library', 'settings-tab-services'])
    screen.getByTestId('media-path-setting-group')
  })

  it('adds the branding and AI tabs in the Plus edition', () => {
    h.withPlusEdition(() => {
      renderComponent()

      expect(tabIds()).toEqual([
        'settings-tab-library',
        'settings-tab-branding',
        'settings-tab-ai',
        'settings-tab-services',
      ])
    })
  })

  it('leaves out the library tab when the storage is not local', () => {
    commonStore.state.storage_driver = 's3'

    renderComponent()

    expect(tabIds()).toEqual(['settings-tab-services'])
    screen.getByTestId('services-setting-group')
  })

  it('shows the picked tab', async () => {
    renderComponent()

    await h.user.click(screen.getByTestId('settings-tab-services'))

    screen.getByTestId('services-setting-group')
    expect(screen.queryByTestId('media-path-setting-group')).toBeNull()
  })
})
