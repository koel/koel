import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './AppleMusicButton.vue'

describe('appleMusicButton.vue', () => {
  const h = createHarness()

  it('renders', () => {
    expect(
      h
        .render(Component, {
          props: {
            url: 'https://music.apple.com/buy-nao',
          },
        })
        .html(),
    ).toMatchSnapshot()
  })
})
