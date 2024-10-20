import { expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { commonStore } from '@/stores/commonStore'
import Component from './PreferencesForm.vue'

new class extends UnitTestCase {
  protected test () {
    it('has "Transcode on mobile" option if supported', () => {
      commonStore.state.supports_transcoding = true
      this.render(Component)
      screen.getByTestId('transcode_on_mobile')
    })

    it('does not have "Transcode on mobile" option if not supported', async () => {
      commonStore.state.supports_transcoding = false
      this.render(Component)
      expect(screen.queryByTestId('transcode_on_mobile')).toBeNull()
    })
  }
}
