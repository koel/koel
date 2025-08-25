import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { preferenceStore } from '@/stores/preferenceStore'
import { playbackService } from '@/services/QueuePlaybackService'
import Component from './RepeatModeSwitch.vue'

describe('repeatModeSwitch.vue', () => {
  const h = createHarness()

  it('changes mode', async () => {
    h.createAudioPlayer()

    const mock = h.mock(playbackService, 'rotateRepeatMode')
    preferenceStore.state.repeat_mode = 'NO_REPEAT'
    h.render(Component)

    await h.user.click(screen.getByRole('button'))

    expect(mock).toHaveBeenCalledOnce()
  })
})
