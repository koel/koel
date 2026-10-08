import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { defineComponent } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import type { SettingsSection } from '@/components/screens/SettingsScreen.vue'
import Component from './SettingsSectionNav.vue'

describe('settingsSectionNav.vue', () => {
  const h = createHarness()

  const Pane = defineComponent({ template: '<div />' })

  const accountOnly: SettingsSection[] = [
    { id: 'profile', label: 'Profile', component: Pane, group: 'Account' },
    { id: 'themes', label: 'Themes', component: Pane, group: 'Account' },
  ]

  const withServer: SettingsSection[] = [
    ...accountOnly,
    { id: 'services', label: 'Services', component: Pane, group: 'Server' },
  ]

  const renderComponent = (sections: SettingsSection[], modelValue = 'profile') =>
    h.render(Component, { props: { sections, modelValue, panelId: 'panel' } })

  it('names the groups when there is more than one', () => {
    renderComponent(withServer)

    expect(screen.getAllByRole('heading', { hidden: true }).map(heading => heading.textContent)).toEqual([
      'Account',
      'Server',
    ])
  })

  it('leaves out the group name when there is only one group', () => {
    renderComponent(accountOnly)

    expect(screen.queryByRole('heading', { hidden: true })).toBeNull()
  })

  it('selects a section from the list', async () => {
    const { emitted } = renderComponent(withServer)

    await h.user.click(screen.getByTestId('settings-section-services'))

    expect(emitted('update:modelValue')?.[0]).toEqual(['services'])
  })

  it('selects a section from the phone picker', async () => {
    const { emitted } = renderComponent(withServer)

    await h.user.selectOptions(screen.getByTestId('settings-section-picker').querySelector('select')!, 'themes')

    expect(emitted('update:modelValue')?.[0]).toEqual(['themes'])
  })
})
