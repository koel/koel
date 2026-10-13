import { afterEach, beforeEach, describe, expect, it, vi } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import type { BasePlaybackService } from '@/services/BasePlaybackService'
import { playbackManager } from '@/services/playbackManager'
import Component from './AudioPlayer.vue'

describe('audioPlayer.vue', () => {
  const h = createHarness()

  const media = { currentTime: 0, duration: 0, buffered: { length: 0 }, readyState: 4, src: '' }
  const seekTo = vi.fn()

  beforeEach(() => {
    vi.useFakeTimers()
    Object.assign(media, { currentTime: 83.4, duration: 296 })
    playbackManager.currentService = { media, seekTo } as unknown as BasePlaybackService
  })

  afterEach(() => {
    vi.useRealTimers()
    seekTo.mockReset()
    playbackManager.currentService = null
  })

  it('exposes the playback position as a slider', async () => {
    h.render(Component)

    await vi.advanceTimersByTimeAsync(250)

    const slider = screen.getByRole('slider', { name: 'Seek' })
    expect(slider.getAttribute('aria-valuemin')).toBe('0')
    expect(slider.getAttribute('aria-valuemax')).toBe('296')
    expect(slider.getAttribute('aria-valuenow')).toBe('83')
    expect(slider.getAttribute('aria-valuetext')).toBe('01:23 of 04:56')

    media.currentTime = 120
    await vi.advanceTimersByTimeAsync(250)

    expect(slider.getAttribute('aria-valuenow')).toBe('120')
    expect(slider.getAttribute('aria-valuetext')).toBe('02:00 of 04:56')
  })

  it('reports no duration for a stream without a known length', async () => {
    media.duration = Infinity
    h.render(Component)

    await vi.advanceTimersByTimeAsync(250)

    const slider = screen.getByRole('slider', { name: 'Seek' })
    expect(slider.getAttribute('aria-valuemax')).toBe('0')
    expect(slider.getAttribute('aria-valuenow')).toBe('0')
  })

  it('seeks to the start with Home', async () => {
    h.render(Component)

    screen.getByRole('slider', { name: 'Seek' }).focus()
    await h.user.keyboard('{Home}')

    expect(seekTo).toHaveBeenCalledWith(0)
  })

  it('seeks to the end with End', async () => {
    h.render(Component)

    screen.getByRole('slider', { name: 'Seek' }).focus()
    await h.user.keyboard('{End}')

    expect(seekTo).toHaveBeenCalledWith(296)
  })
})
