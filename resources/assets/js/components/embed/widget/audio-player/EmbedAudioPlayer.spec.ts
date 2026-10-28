import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './EmbedAudioPlayer.vue'

describe('embedAudioPlayer.vue', () => {
  const h = createHarness()

  const renderComponent = (preview: boolean) => {
    h.render(Component, {
      props: {
        playables: [h.factory('song').make()],
        preview,
      },
    })

    const audio = document.querySelector('audio')!
    Object.defineProperty(audio, 'duration', { value: 200, configurable: true })
    audio.dispatchEvent(new Event('loadedmetadata'))

    return audio
  }

  const playAt = (audio: HTMLAudioElement, seconds: number) => {
    Object.defineProperty(audio, 'currentTime', { value: seconds, configurable: true, writable: true })
    audio.dispatchEvent(new Event('timeupdate'))
  }

  it('fades a preview in over its first seconds', () => {
    const audio = renderComponent(true)
    expect(audio.volume).toBe(0)

    playAt(audio, 1.5)

    expect(audio.volume).toBeCloseTo(0.375)
  })

  it('reaches full volume once a preview passes the fade-in', () => {
    const audio = renderComponent(true)

    playAt(audio, 3.1)

    expect(audio.volume).toBe(0.75)
  })

  it('starts a full song at full volume', () => {
    const audio = renderComponent(false)

    expect(audio.volume).toBe(0.75)
  })
})
