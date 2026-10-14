import { fireEvent, screen } from '@testing-library/vue'
import type { Ref } from 'vue'
import { ref } from 'vue'
import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import {
  FilteredPlayablesKey,
  PlayableListConfigKey,
  PlayableListContextKey,
  PlayableListSortFieldKey,
  SelectedPlayablesKey,
} from '@/config/symbols'

const reapplySelectionSpy = vi.fn()
const listedRows = vi.hoisted(() => ({ current: null as Ref<PlayableRow[]> | null }))

vi.mock('@/composables/useListSelection', async importOriginal => {
  const original = await importOriginal<typeof import('@/composables/useListSelection')>()

  return {
    useListSelection: (...args: Parameters<typeof original.useListSelection>) => {
      listedRows.current = args[0] as Ref<PlayableRow[]>
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

import Component from './PlayableList.vue'

describe('playableList.vue', () => {
  const h = createHarness({
    beforeEach: () => reapplySelectionSpy.mockClear(),
  })

  const renderComponent = (songs: Song[]) => {
    const playables = ref<Playable[]>(songs)
    const selectedPlayables = ref<Playable[]>([])
    const setSelectedPlayables = vi.fn((value: Playable[]) => (selectedPlayables.value = value))

    h.render(Component, {
      global: {
        provide: {
          [<symbol>FilteredPlayablesKey]: [playables],
          [<symbol>SelectedPlayablesKey]: [selectedPlayables, setSelectedPlayables],
          [<symbol>PlayableListSortFieldKey]: [ref(null)],
          [<symbol>PlayableListConfigKey]: [{}],
          [<symbol>PlayableListContextKey]: [{}],
        },
      },
    })

    return { playables, selectedPlayables, setSelectedPlayables }
  }

  const selectSongsAt = async (...indexes: number[]) => {
    const items = screen.getAllByTestId('song-item')

    for (const index of indexes) {
      await fireEvent.click(items[index], { ctrlKey: true })
    }
  }

  const selectedIds = (selectedPlayables: Ref<Playable[]>) => selectedPlayables.value.map(({ id }) => id).sort()

  it('reuses the rows of songs that stay in the list', async () => {
    const songs = h.factory('song').make(3)
    const { playables } = renderComponent(songs)
    const [firstRow, secondRow] = listedRows.current!.value

    playables.value = [...songs.slice(0, 2), h.factory('song').make()]
    await h.tick()

    expect(listedRows.current!.value[0]).toBe(firstRow)
    expect(listedRows.current!.value[1]).toBe(secondRow)
  })

  it('keeps the selection when songs are removed', async () => {
    const songs = h.factory('song').make(5)
    const { playables, selectedPlayables } = renderComponent(songs)
    await selectSongsAt(1, 3)

    playables.value = songs.filter((_, index) => index !== 0 && index !== 3)
    await h.tick()

    expect(selectedIds(selectedPlayables)).toEqual([songs[1].id])
    expect(screen.getAllByTestId('song-item')[0].classList.contains('selected')).toBe(true)
  })

  it('keeps the selection when songs are added', async () => {
    const songs = h.factory('song').make(3)
    const { playables, selectedPlayables } = renderComponent(songs)
    await selectSongsAt(1)

    playables.value.push(...h.factory('song').make(2))
    await h.tick()

    expect(selectedIds(selectedPlayables)).toEqual([songs[1].id])
    expect(screen.getAllByTestId('song-item')).toHaveLength(5)
  })

  it('keeps the selection when songs are reordered', async () => {
    const songs = h.factory('song').make(4)
    const { playables, selectedPlayables } = renderComponent(songs)
    const expectedIds = [songs[0].id, songs[2].id].sort()
    await selectSongsAt(0, 2)

    playables.value.reverse()
    await h.tick()

    const items = screen.getAllByTestId('song-item')

    expect(selectedIds(selectedPlayables)).toEqual(expectedIds)
    expect(items.map(item => item.classList.contains('selected'))).toEqual([false, true, false, true])
  })

  it('does not reapply the selection when a song property changes', async () => {
    const songs = h.factory('song').make(3)
    const { playables, setSelectedPlayables } = renderComponent(songs)
    await selectSongsAt(0)

    reapplySelectionSpy.mockClear()
    setSelectedPlayables.mockClear()

    playables.value[0].playback_state = 'Playing'
    playables.value[0].favorite = !playables.value[0].favorite
    await h.tick()

    expect(reapplySelectionSpy).not.toHaveBeenCalled()
    expect(setSelectedPlayables).not.toHaveBeenCalled()
  })
})
