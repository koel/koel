import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './AudioPlayer.vue'

describe('audioPlayer', () => {
  const h = createHarness()

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
})
