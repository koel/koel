import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './RankedColumns.vue'

describe('rankedColumns.vue', () => {
  const h = createHarness()

  const makeItems = (count: number) =>
    Array.from({ length: count }, (_, i) => ({
      key: `item-${i}`,
      title: `Item ${i + 1}`,
      subtitle: 'Some subtitle',
      href: `#/item/${i}`,
    }))

  it('splits the items into three columns of equal height', () => {
    const { container } = h.render(Component, { props: { items: makeItems(12) } })

    expect(container.querySelector<HTMLElement>('ol')!.style.gridTemplateRows).toBe('repeat(4, auto)')
  })

  it('leaves out the divider under the last item of each column', () => {
    h.render(Component, { props: { items: makeItems(12) } })

    const lastInColumn = screen
      .getAllByRole('listitem')
      .map((item, i) => (item.classList.contains('last-in-column') ? i + 1 : null))
      .filter(Boolean)

    expect(lastInColumn).toEqual([4, 8, 12])
  })
})
