import { fireEvent } from '@testing-library/vue'
import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { playbackManager } from '@/services/playbackManager'
import { crossfadeService } from '@/services/crossfadeService'
import Component from './AudioPlayer.vue'

describe('audioPlayer', () => {
  const h = createHarness({
    afterEach: () => {
      crossfadeService.cancel()
      playbackManager.currentService = null
      vi.useRealTimers()
    },
  })

  it('renders the audio element', () => {
    const { container } = h.render(Component)
    expect(container.querySelector<HTMLAudioElement>('#audio-player')).toBeTruthy()
  })

  it('renders the hit area and track structure', () => {
    const { container } = h.render(Component)
    expect(container.querySelector('.hit-area')).toBeTruthy()
    expect(container.querySelector('.track')).toBeTruthy()
    expect(container.querySelector('.progress-played')).toBeTruthy()
    expect(container.querySelector('.progress-buffer')).toBeTruthy()
    expect(container.querySelector('.progress-hover')).toBeTruthy()
  })

  it('commits a drag seek once when the pointer is released', async () => {
    const { container } = h.render(Component)
    const audio = container.querySelector<HTMLAudioElement>('#audio-player')!
    const hitArea = container.querySelector<HTMLElement>('.hit-area')!
    const track = container.querySelector<HTMLElement>('.track')!
    const playbackService = playbackManager.useQueuePlayback(audio)
    const seekMock = h.mock(playbackService, 'seekTo')

    h.setReadOnlyProperty(audio, 'duration', 200)
    h.mock(track, 'getBoundingClientRect', {
      left: 0,
      width: 100,
    })

    await fireEvent.pointerDown(hitArea, { button: 0, clientX: 25 })
    await fireEvent.pointerMove(document, { clientX: 75 })
    expect(seekMock).not.toHaveBeenCalled()

    await fireEvent.pointerUp(document, { clientX: 75 })
    await fireEvent.click(hitArea, { clientX: 75 })

    expect(seekMock).toHaveBeenCalledTimes(1)
    expect(seekMock).toHaveBeenCalledWith(150)
  })

  it('does not suppress the next seek after a drag is released outside the seek bar', async () => {
    const { container } = h.render(Component)
    const audio = container.querySelector<HTMLAudioElement>('#audio-player')!
    const hitArea = container.querySelector<HTMLElement>('.hit-area')!
    const track = container.querySelector<HTMLElement>('.track')!
    const playbackService = playbackManager.useQueuePlayback(audio)
    const seekMock = h.mock(playbackService, 'seekTo')

    h.setReadOnlyProperty(audio, 'duration', 200)
    h.mock(track, 'getBoundingClientRect', {
      left: 0,
      width: 100,
    })

    hitArea.dispatchEvent(new MouseEvent('pointerdown', { bubbles: true, button: 0, clientX: 25 }))
    document.dispatchEvent(new MouseEvent('pointermove', { bubbles: true, clientX: 75 }))
    document.dispatchEvent(new MouseEvent('pointerup', { bubbles: true, clientX: 75 }))
    await h.tick()
    expect(seekMock).toHaveBeenCalledWith(150)

    await new Promise(resolve => window.setTimeout(resolve))
    await fireEvent.click(hitArea, { clientX: 25 })

    expect(seekMock).toHaveBeenCalledTimes(2)
    expect(seekMock).toHaveBeenLastCalledWith(50)
  })

  it('renders progress from logical playback metrics', async () => {
    vi.useFakeTimers()
    const media = document.createElement('audio')
    h.setReadOnlyProperty(media, 'src', 'http://test/play/song')
    h.setReadOnlyProperty(media, 'readyState', 4)
    h.setReadOnlyProperty(playbackManager, 'currentService', {
      media,
      position: 100,
      duration: 400,
      bufferedThrough: 200,
    })

    const { container } = h.render(Component)
    vi.advanceTimersByTime(250)
    await h.tick()

    expect(container.querySelector<HTMLElement>('.progress-played')?.style.width).toBe('25%')
    expect(container.querySelector<HTMLElement>('.progress-buffer')?.style.width).toBe('50%')
  })

  it('renders canonical outgoing progress while a crossfade is active', async () => {
    vi.useFakeTimers()
    const outgoingMedia = document.createElement('audio')
    const incomingMedia = document.createElement('audio')
    const incomingSong = h.factory('song').make({ length: 100 })
    h.setReadOnlyProperty(outgoingMedia, 'src', 'http://test/play/outgoing')
    h.setReadOnlyProperty(outgoingMedia, 'readyState', 4)
    h.setReadOnlyProperty(incomingMedia, 'currentTime', 80)
    h.setReadOnlyProperty(incomingMedia, 'duration', 100)
    h.setReadOnlyProperty(playbackManager, 'currentService', {
      media: outgoingMedia,
      position: 25,
      duration: 100,
      bufferedThrough: 50,
    })
    crossfadeService.state = {
      incomingAudio: incomingMedia,
      playable: incomingSong,
      rafId: 0,
      originalVolume: 7,
      progressive: false,
      ready: true,
      failed: false,
    }

    const { container } = h.render(Component)
    vi.advanceTimersByTime(250)
    await h.tick()

    expect(container.querySelector<HTMLElement>('.progress-played')?.style.width).toBe('25%')
    expect(container.querySelector<HTMLElement>('.progress-buffer')?.style.width).toBe('50%')
  })
})
