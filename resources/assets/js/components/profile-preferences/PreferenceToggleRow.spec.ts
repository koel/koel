import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { http } from '@/services/http'
import { preferenceStore as preferences } from '@/stores/preferenceStore'
import Component from './PreferenceToggleRow.vue'

describe('preferenceToggleRow', () => {
  const h = createHarness()

  it('toggles its preference', async () => {
    h.mock(http, 'patch')
    preferences.temporary.show_waveform = false
    h.render(Component, { props: { preference: 'show_waveform', label: 'Show waveform' } })

    await h.user.click(screen.getByRole('checkbox'))

    expect(preferences.show_waveform).toBe(true)
  })

  it('uses the given name over the preference key', () => {
    h.render(Component, { props: { preference: 'confirm_before_closing', label: 'Confirm', name: 'confirm_closing' } })

    expect(screen.getByRole('checkbox').getAttribute('name')).toBe('confirm_closing')
  })
})
