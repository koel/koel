import { describe, it } from 'vitest'
import { ref } from 'vue'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { CurrentStreamableKey } from '@/config/symbols'
import Component from './YouTubeScreen.vue'

describe('youTubeScreen.vue', () => {
  const h = createHarness()

  it('shows placeholder when no video is playing', () => {
    h.render(Component, {
      global: {
        provide: {
          [CurrentStreamableKey as symbol]: ref(null),
        },
      },
    })

    screen.getByText('YouTube videos will be played here.')
  })
})
