import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './SettingsScreen.vue'

describe('settingsScreen.vue', () => {
  const h = createHarness()

  const renderComponent = () => {
    return h.render(Component, {
      global: {
        stubs: {
          MediaPathSettingGroup: h.stub('media-path-setting-group'),
          BrandingSettingGroup: h.stub('branding-setting-group'),
        },
      },
    })
  }

  it('does not have the branding setting group in Community license', () => {
    renderComponent()
    screen.getByTestId('media-path-setting-group')
    expect(screen.queryByTestId('branding-setting-group')).toBeNull()
  })

  it('has the branding settings in Plus license', () => {
    h.withPlusEdition(() => {
      renderComponent()
      screen.getByTestId('media-path-setting-group')
      screen.getByTestId('branding-setting-group')
    })
  })
})
