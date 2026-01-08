import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import en from '@/locales/en.json'
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

    screen.getByTitle(en.ui.sorting.sortingBy.replace('{label}', en.podcasts.sortFields.title).replace('{order}', en.ui.sorting.descending))

    await h.user.click(screen.getByTitle(en.ui.sorting.sortBy.replace('{label}', en.podcasts.sortFields.title)))
    expect(emitted().sort[0]).toEqual(['title', 'asc'])

    await h.user.click(screen.getByTitle(en.ui.sorting.sortBy.replace('{label}', en.podcasts.sortFields.author)))
    expect(emitted().sort[1]).toEqual(['author', 'asc'])
  })
})
