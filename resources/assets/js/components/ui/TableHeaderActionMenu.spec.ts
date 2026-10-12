import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import {
  albumTableColumnConfig,
  albumTableMenuItems,
  radioStationTableColumnConfig,
  radioStationTableMenuItems,
} from '@/config/tables'
import Component from './TableHeaderActionMenu.vue'

describe('tableHeaderActionMenu.vue', () => {
  const h = createHarness()

  const renderAlbumMenu = () =>
    h.render(Component, {
      props: { field: 'name', order: 'asc', items: albumTableMenuItems, columnConfig: albumTableColumnConfig },
    })

  it('renders a row per column', async () => {
    renderAlbumMenu()

    await h.user.click(screen.getByRole('button', { name: 'Sort' }))

    for (const label of ['Name', 'Artist', 'Time', 'Year', 'Rating', 'Favorite']) {
      screen.getByText(label)
    }
  })

  it('sorts by the clicked row', async () => {
    const { emitted } = renderAlbumMenu()

    await h.user.click(screen.getByRole('button', { name: 'Sort' }))
    await h.user.click(screen.getByText('Rating'))

    expect(emitted('sort')?.[0]).toEqual(['rating'])
  })

  it('disables the checkbox of an always-visible column', async () => {
    renderAlbumMenu()

    await h.user.click(screen.getByRole('button', { name: 'Sort' }))

    const nameRow = screen.getByText('Name').closest('li')!
    expect(nameRow.querySelector<HTMLInputElement>('input[type="checkbox"]')!.disabled).toBe(true)
  })

  it('does not sort by a column without a sort field', async () => {
    const { emitted } = h.render(Component, {
      props: {
        field: 'name',
        order: 'asc',
        items: radioStationTableMenuItems,
        columnConfig: radioStationTableColumnConfig,
      },
    })

    await h.user.click(screen.getByRole('button', { name: 'Sort' }))
    await h.user.click(screen.getByText('Description'))

    expect(emitted('sort')).toBeUndefined()
  })
})
