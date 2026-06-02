import { describe, expect, it } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './AlbumTableHeaderActionMenu.vue'

describe('albumTableHeaderActionMenu.vue', () => {
  const h = createHarness()

  it('renders all toggleable columns as menu rows', async () => {
    h.render(Component, { props: { field: 'name' as const, order: 'asc' as const } })

    await h.user.click(screen.getByRole('button', { name: 'Sort' }))

    for (const label of ['Name', 'Artist', 'Time', 'Year', 'Rating', 'Favorite']) {
      screen.getByText(label)
    }
  })

  it('emits sort when a menu row is clicked', async () => {
    const { emitted } = h.render(Component, { props: { field: 'name' as const, order: 'asc' as const } })

    await h.user.click(screen.getByRole('button', { name: 'Sort' }))
    await h.user.click(screen.getByText('Rating'))

    expect(emitted('sort')?.[0]).toEqual(['rating'])
  })

  it('disables the Name row checkbox (always-visible column)', async () => {
    h.render(Component, { props: { field: 'name' as const, order: 'asc' as const } })

    await h.user.click(screen.getByRole('button', { name: 'Sort' }))

    const nameRow = screen.getByText('Name').closest('li')!
    const checkbox = nameRow.querySelector('input[type="checkbox"]') as HTMLInputElement
    expect(checkbox.disabled).toBe(true)
  })
})
