import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import en from '@/locales/en.json'
import Component from './GenreListSorter.vue'

describe('genreListSorter.vue', () => {
  const h = createHarness()

  it('renders and emits the proper event', async () => {
    const { emitted } = h.render(Component, {
      props: {
        field: 'name',
        order: 'asc',
      },
    })

    screen.getByTitle(en.ui.sorting.sortingBy.replace('{label}', en.albums.name).replace('{order}', en.ui.sorting.ascending))

    await h.user.click(screen.getByTitle(en.ui.sorting.sortBy.replace('{label}', en.albums.name)))
    expect(emitted().sort[0]).toEqual(['name', 'desc'])

    await h.user.click(screen.getByTitle(en.ui.sorting.sortBy.replace('{label}', en.songs.songCount)))
    expect(emitted().sort[1]).toEqual(['song_count', 'asc'])
  })
})
