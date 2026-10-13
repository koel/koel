import { fireEvent, screen } from '@testing-library/vue'
import { reactive } from 'vue'
import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'

const reapplySelectionSpy = vi.fn()

vi.mock('@/composables/useListSelection', async importOriginal => {
  const original = await importOriginal<typeof import('@/composables/useListSelection')>()

  return {
    useListSelection: (...args: Parameters<typeof original.useListSelection>) => {
      const selection = original.useListSelection(...args)

      return {
        ...selection,
        reapplySelection: () => {
          reapplySelectionSpy()
          selection.reapplySelection()
        },
      }
    },
  }
})

import Component from './MediaListView.vue'

describe('mediaListView.vue', () => {
  const h = createHarness({
    beforeEach: () => reapplySelectionSpy.mockClear(),
  })

  const renderComponent = (items: Song[]) => h.render(Component, { props: { items, folderId: null } })

  const rowSelectionStates = () => screen.getAllByRole('article').map(row => row.classList.contains('selected'))

  it('keeps the selection when the items change', async () => {
    const songs = h.factory('song').make(3)
    const { rerender } = renderComponent(songs)

    await fireEvent.click(screen.getAllByRole('article')[0], { ctrlKey: true })
    await rerender({ items: [...songs].reverse().concat(h.factory('song').make(1)), folderId: null })

    expect(rowSelectionStates()).toEqual([false, false, true, false])
  })

  it('does not reapply the selection when an item property changes', async () => {
    const songs = reactive(h.factory('song').make(3))
    renderComponent(songs)

    songs[0].playback_state = 'Playing'
    songs[0].favorite = !songs[0].favorite
    await h.tick()

    expect(reapplySelectionSpy).not.toHaveBeenCalled()
  })
})
