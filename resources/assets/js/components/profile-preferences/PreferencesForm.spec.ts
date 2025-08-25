import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { commonStore } from '@/stores/commonStore'
import Component from './PreferencesForm.vue'

describe('preferencesForm.vue', () => {
  const h = createHarness()

  it('has "Transcode on mobile" option if supported', () => {
    commonStore.state.supports_transcoding = true
    h.render(Component)
    screen.getByTestId('transcode_on_mobile')
  })

  it('does not have "Transcode on mobile" option if not supported', async () => {
    commonStore.state.supports_transcoding = false
    h.render(Component)
    expect(screen.queryByTestId('transcode_on_mobile')).toBeNull()
  })
})
