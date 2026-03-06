import { describe, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './PlayableExcerptResultsBlock.vue'

describe('playableExcerptResultsBlock.vue', () => {
  const h = createHarness()

  it('shows "Nothing found." when no playables', () => {
    h.render(Component, {
      props: { playables: [], searching: false },
    })

    screen.getByText('Nothing found.')
  })
})
