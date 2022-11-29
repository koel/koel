import { expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import isMobile from 'ismobilejs'
import UnitTestCase from '@/__tests__/UnitTestCase'
import PreferencesForm from './PreferencesForm.vue'

new class extends UnitTestCase {
  protected test () {
    it('has "Transcode on mobile" option for mobile users', () => {
      isMobile.phone = true
      this.render(PreferencesForm)
      screen.getByRole('checkbox', { name: 'Convert and play media at 128kbps on mobile' })
    })

    it('does not have "Transcode on mobile" option for non-mobile users', async () => {
      isMobile.phone = false
      this.render(PreferencesForm)
      expect(screen.queryByRole('checkbox', { name: 'Convert and play media at 128kbps on mobile' })).toBeNull()
    })
  }
}
