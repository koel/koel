import { expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { preferenceStore } from '@/stores/preferenceStore'
import { playbackService } from '@/services/playbackService'
import RepeatModeSwitch from './RepeatModeSwitch.vue'

new class extends UnitTestCase {
  protected test () {
    it('changes mode', async () => {
      const mock = this.mock(playbackService, 'rotateRepeatMode')
      preferenceStore.state.repeat_mode = 'NO_REPEAT'
      this.render(RepeatModeSwitch)

      await this.user.click(screen.getByRole('button'))

      expect(mock).toHaveBeenCalledOnce()
    })
  }
}
