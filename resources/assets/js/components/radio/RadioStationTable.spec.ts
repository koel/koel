import { describe, expect, it, vi } from 'vite-plus/test'
import type { Mock } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { assertOpenContextMenu } from '@/__tests__/assertions'
import { useContextMenu } from '@/composables/useContextMenu'
import RadioStationContextMenu from './RadioStationContextMenu.vue'
import SelectedRadioStationsContextMenu from './SelectedRadioStationsContextMenu.vue'
import Component from './RadioStationTable.vue'

const virtualScrollerStub = {
  name: 'VirtualScrollerStub',
  props: ['items', 'itemHeight', 'carriesScreenHeader'],
  template:
    '<div><slot name="before" /><template v-for="item in items" :key="item.item.id"><slot :item="item" /></template></div>',
}

vi.mock('@/composables/useContextMenu')

describe('radioStationTable.vue', () => {
  const h = createHarness()

  const renderWithStations = (count = 3) => {
    const stations = count === 0 ? [] : h.factory('radio-station').make(count)
    return {
      stations,
      ...h.render(Component, {
        props: { stations, field: 'name' as const, order: 'asc' as const },
        global: { stubs: { VirtualScroller: virtualScrollerStub } },
      }),
    }
  }

  it('emits sort with toggled order when a header is clicked', async () => {
    const { emitted } = renderWithStations(0)

    await h.user.click(screen.getByRole('button', { name: 'Name' }))

    expect(emitted('sort')?.[0]).toEqual(['name', 'desc'])
  })

  it('opens the station menu when right-clicking a row', async () => {
    const { openContextMenu } = useContextMenu()
    const { stations } = renderWithStations()

    await h.trigger(screen.getAllByTestId('radio-station-row')[1], 'contextMenu')

    await assertOpenContextMenu(openContextMenu as Mock, RadioStationContextMenu, { station: stations[1] })
  })

  it('opens the menu for all selected rows when several are selected', async () => {
    const { openContextMenu } = useContextMenu()
    const { stations } = renderWithStations()
    const rows = screen.getAllByTestId('radio-station-row')

    await h.trigger(rows[0], 'click')
    await h.trigger(rows[2], 'click', { shiftKey: true })
    await h.trigger(rows[1], 'contextMenu')

    await assertOpenContextMenu(openContextMenu as Mock, SelectedRadioStationsContextMenu, { stations })
  })
})
