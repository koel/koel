import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './PodcastListSorter.vue'

describe('podcastListSorter.vue', () => {
  const h = createHarness()

  it('renders and emits the proper event', async () => {
    const { emitted } = h.render(Component, {
      props: {
        field: 'title',
        order: 'desc',
      },
    })

    screen.getByTitle('Sorting by Title, descending')

    await h.user.click(screen.getByTitle('Sort by Title'))
    expect(emitted().sort[0]).toEqual(['title', 'asc'])

    await h.user.click(screen.getByTitle('Sort by Author'))
    expect(emitted().sort[1]).toEqual(['author', 'asc'])
  })
})
