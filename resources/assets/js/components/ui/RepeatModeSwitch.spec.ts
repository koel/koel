import { expect, it } from 'vitest'
import { preferenceStore } from '@/stores'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { screen } from '@testing-library/vue'
import { playbackService } from '@/services'
import RepeatModeSwitch from './RepeatModeSwitch.vue'

new class extends UnitTestCase {
  protected test () {
    it('changes mode', async () => {
      const mock = this.mock(playbackService, 'changeRepeatMode')
      preferenceStore.state.repeat_mode = 'NO_REPEAT'
      this.render(RepeatModeSwitch)

      await this.user.click(screen.getByRole('button'))

      expect(mock).toHaveBeenCalledOnce()
    })
  }
}
