import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import en from '@/locales/en.json'
import Component from './AlbumListSorter.vue'

describe('albumListSorter.vue', () => {
  const h = createHarness()

  it('renders and emits the proper event', async () => {
    const { emitted } = h.render(Component, {
      props: {
        field: 'name',
        order: 'asc',
      },
    })

    screen.getByTitle(en.ui.sorting.sortingBy.replace('{label}', en.albums.sortFields.name).replace('{order}', en.ui.sorting.ascending))

    await h.user.click(screen.getByTitle(en.ui.sorting.sortBy.replace('{label}', en.albums.sortFields.name)))
    expect(emitted().sort[0]).toEqual(['name', 'desc'])

    await h.user.click(screen.getByTitle(en.ui.sorting.sortBy.replace('{label}', en.albums.sortFields.year)))
    expect(emitted().sort[1]).toEqual(['year', 'asc'])

    await h.user.click(screen.getByTitle(en.ui.sorting.sortBy.replace('{label}', en.albums.sortFields.artist_name)))
    expect(emitted().sort[2]).toEqual(['artist_name', 'asc'])

    await h.user.click(screen.getByTitle(en.ui.sorting.sortBy.replace('{label}', en.albums.sortFields.created_at)))
    expect(emitted().sort[3]).toEqual(['created_at', 'asc'])
  })
})
