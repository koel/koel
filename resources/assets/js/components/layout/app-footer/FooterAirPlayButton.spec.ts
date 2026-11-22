import { describe, expect, it, vi } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { playback } from '@/services/playbackManager'
import Component from './FooterAirPlayButton.vue'

describe('footerAirPlayButton.vue', () => {
  const h = createHarness({
    beforeEach: () => h.createAudioPlayer(),
  })

  const announceTargetAvailability = async (availability: string) => {
    const event = Object.assign(new Event('webkitplaybacktargetavailabilitychanged'), { availability })
    document.querySelector('#audio-player')!.dispatchEvent(event)
    await h.tick()
  }

  it('shows only while an AirPlay target is available', async () => {
    h.render(Component)

    expect(screen.queryByRole('button', { name: 'AirPlay' })).toBeNull()

    await announceTargetAvailability('available')
    screen.getByRole('button', { name: 'AirPlay' })

    await announceTargetAvailability('not-available')
    expect(screen.queryByRole('button', { name: 'AirPlay' })).toBeNull()
  })

  it('opens the AirPlay picker for the playing media', async () => {
    const media = playback().media
    media.webkitShowPlaybackTargetPicker = vi.fn()

    h.render(Component)
    await announceTargetAvailability('available')
    await h.user.click(screen.getByRole('button', { name: 'AirPlay' }))

    expect(media.webkitShowPlaybackTargetPicker).toHaveBeenCalled()
  })

  it('highlights while playing over AirPlay', async () => {
    const media = playback().media
    media.webkitShowPlaybackTargetPicker = vi.fn()

    h.render(Component)
    await announceTargetAvailability('available')
    await h.user.click(screen.getByRole('button', { name: 'AirPlay' }))

    h.setReadOnlyProperty(media, 'webkitCurrentPlaybackTargetIsWireless', true)
    media.dispatchEvent(new Event('webkitcurrentplaybacktargetiswirelesschanged'))
    await h.tick()

    expect(screen.getByRole('button', { name: 'Playing on AirPlay' }).classList.contains('wireless')).toBe(true)

    h.setReadOnlyProperty(media, 'webkitCurrentPlaybackTargetIsWireless', false)
  })
})
