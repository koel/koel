import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import en from '@/locales/en.json'
import Component from './ArtistListSorter.vue'

describe('artistListSorter.vue', () => {
  const h = createHarness()

  it('renders and emits the proper event', async () => {
    const { emitted } = h.render(Component, {
      props: {
        field: 'name',
        order: 'asc',
      },
    })

    screen.getByTitle(en.ui.sorting.sortingBy.replace('{label}', en.artists.sortFields.name).replace('{order}', en.ui.sorting.ascending))

    await h.user.click(screen.getByTitle(en.ui.sorting.sortBy.replace('{label}', en.artists.sortFields.name)))
    expect(emitted().sort[0]).toEqual(['name', 'desc'])

    await h.user.click(screen.getByTitle(en.ui.sorting.sortBy.replace('{label}', en.artists.sortFields.dateAdded)))
    expect(emitted().sort[1]).toEqual(['created_at', 'asc'])
  })
})
