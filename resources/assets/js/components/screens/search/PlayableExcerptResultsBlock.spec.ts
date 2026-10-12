import { describe, expect, it } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Router from '@/router'
import Component from './PlayableExcerptResultsBlock.vue'

describe('playableExcerptResultsBlock.vue', () => {
  const h = createHarness()

  it('shows "Nothing found." when no playables', () => {
    h.render(Component, {
      props: { playables: [], searching: false },
    })

    screen.getByText('Nothing found.')
  })
  it('goes to the song results with the query encoded', async () => {
    const goMock = h.mock(Router, 'go')

    h.render(Component, {
      props: { playables: h.factory('song').make(3), query: 'R&B #1 + more' },
    })

    await h.user.click(screen.getByTestId('view-all-songs-btn'))

    expect(goMock).toHaveBeenCalledWith('/#/search/songs/?q=R%26B+%231+%2B+more')
  })
})
