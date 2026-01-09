import { describe, expect, it, vi } from 'vitest'
import { fireEvent, screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { socketService } from '@/services/socketService'
import { volumeManager } from '@/services/volumeManager'
import { preferenceStore } from '@/stores/preferenceStore'
import Component from './VolumeSlider.vue'

describe('volumeSlider.vue', () => {
  const h = createHarness({
    beforeEach: () => {
      // Mock performance.now() and Date.now() to avoid vue-i18n timestamp issues with fake timers
      const now = Date.now()
      vi.setSystemTime(now)
      vi.spyOn(performance, 'now').mockReturnValue(now)
      vi.useFakeTimers({ now })
      preferenceStore.volume = 5
    },
    afterEach: () => {
      vi.restoreAllMocks()
      vi.useRealTimers()
      vi.useRealTimers()
    },
  })

  it('mutes and unmutes', async () => {
    const { html } = h.render(Component)
    expect(html()).toMatchSnapshot()
    expect(volumeManager.volume.value).toEqual(5)

    await h.user.click(screen.getByTitle('Mute'))
    expect(html()).toMatchSnapshot()
    expect(volumeManager.volume.value).toEqual(0)

    await h.user.click(screen.getByTitle('Unmute'))
    expect(html()).toMatchSnapshot()
    expect(volumeManager.volume.value).toEqual(5)
  })

  it('sets and broadcasts volume', async () => {
    const broadcastMock = h.mock(socketService, 'broadcast')
    h.render(Component)

    await fireEvent.update(screen.getByRole('slider'), '4.2')
    vi.runAllTimers()

    expect(volumeManager.volume.value).toBe(4.2)
    expect(broadcastMock).toHaveBeenCalledWith('SOCKET_VOLUME_CHANGED', 4.2)
  })
})
