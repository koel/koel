import { expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { commonStore } from '@/stores'
import UnitTestCase from '@/__tests__/UnitTestCase'
import PreferencesForm from './PreferencesForm.vue'

new class extends UnitTestCase {
  protected test () {
    it('has "Transcode on mobile" option if supported', () => {
      commonStore.state.supports_transcoding = true
      this.render(PreferencesForm)
      screen.getByTestId('transcode_on_mobile')
    })

    it('does not have "Transcode on mobile" option if not supported', async () => {
      commonStore.state.supports_transcoding = false
      this.render(PreferencesForm)
      expect(screen.queryByTestId('transcode_on_mobile')).toBeNull()
    })
  }
}
