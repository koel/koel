import { describe, expect, it } from 'vite-plus/test'
import { screen, within } from '@testing-library/vue'
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

    screen.getByRole('button', { name: 'Title descending' })

    await h.user.click(within(screen.getByRole('list')).getByText('Title'))
    expect(emitted().sort[0]).toEqual(['title', 'asc'])

    await h.user.click(within(screen.getByRole('list')).getByText('Author'))
    expect(emitted().sort[1]).toEqual(['author', 'asc'])
  })
})
