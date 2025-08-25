import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
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

    screen.getByTitle('Sorting by Name, ascending')

    await h.user.click(screen.getByTitle('Sort by Name'))
    expect(emitted().sort[0]).toEqual(['name', 'desc'])

    await h.user.click(screen.getByTitle('Sort by Release Year'))
    expect(emitted().sort[1]).toEqual(['year', 'asc'])

    await h.user.click(screen.getByTitle('Sort by Artist'))
    expect(emitted().sort[2]).toEqual(['artist_name', 'asc'])

    await h.user.click(screen.getByTitle('Sort by Date Added'))
    expect(emitted().sort[3]).toEqual(['created_at', 'asc'])
  })
})
