import { describe, expect, it } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './RadioStationTable.vue'

const virtualScrollerStub = {
  name: 'VirtualScrollerStub',
  props: ['items', 'itemHeight'],
  template: '<div><template v-for="item in items" :key="item.id"><slot :item="item" /></template></div>',
}

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

  it('renders a sort-by-name header', () => {
    renderWithStations(3)

    screen.getByTitle('Sort by name')
  })

  it('emits sort with toggled order when a header is clicked', async () => {
    const { emitted } = renderWithStations(0)

    await h.user.click(screen.getByTitle('Sort by name'))

    expect(emitted('sort')?.[0]).toEqual(['name', 'desc'])
  })

  it('shows a more-actions button in the header action menu', () => {
    renderWithStations(0)

    screen.getByRole('button', { name: 'Sort' })
  })
})
