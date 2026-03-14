import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './AudioPlayer.vue'

describe('audioPlayer.vue', () => {
  const h = createHarness()

  it('renders', () => {
    const { html } = h.render(Component)
    expect(html()).toMatchSnapshot()
  })
})
