import { screen, within } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
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

    screen.getByRole('button', { name: 'Name ascending' })

    await h.user.click(within(screen.getByRole('list')).getByText('Name'))
    expect(emitted().sort[0]).toEqual(['name', 'desc'])

    await h.user.click(within(screen.getByRole('list')).getByText('Date Added'))
    expect(emitted().sort[1]).toEqual(['created_at', 'asc'])
  })
})
