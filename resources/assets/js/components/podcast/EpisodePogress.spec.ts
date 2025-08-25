import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './EpisodeProgress.vue'

describe('episodeProgress.vue', () => {
  const h = createHarness()

  it('renders', () => {
    const { html } = h.render(Component, {
      props: {
        episode: h.factory('episode', {
          length: 300,
        }),
        position: 60,
      },
    })

    expect(html()).toMatchSnapshot()
  })
})
