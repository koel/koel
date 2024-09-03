import { expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import isMobile from 'ismobilejs'
import { commonStore } from '@/stores'
import UnitTestCase from '@/__tests__/UnitTestCase'
import PreferencesForm from './PreferencesForm.vue'

new class extends UnitTestCase {
  protected test () {
    it('has "Transcode on mobile" option for mobile users', () => {
      isMobile.phone = true
      this.render(PreferencesForm)
      screen.getByTestId('transcode_on_mobile')
    })

    it('does not have "Transcode on mobile" option for non-mobile users', async () => {
      isMobile.phone = false
      this.render(PreferencesForm)
      expect(screen.queryByTestId('transcode_on_mobile')).toBeNull()
    })

    it('does not have "Transcode on mobile" option if transcoding is not supported', async () => {
      isMobile.phone = true
      commonStore.state.supports_transcoding = false
      expect(screen.queryByTestId('transcode_on_mobile')).toBeNull()
    })
  }
}
