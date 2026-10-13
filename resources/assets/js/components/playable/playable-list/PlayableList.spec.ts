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

  const renderedRowIds = () => screen.getAllByRole('option').map(row => row.dataset.playableId)

  const tabbableRowIds = () =>
    screen
      .getAllByRole('option')
      .filter(row => row.tabIndex === 0)
      .map(row => row.dataset.playableId)

  const pressKey = async (key: string) => {
    await h.user.keyboard(key)
    await h.tick(2)
  }

  it('makes only the first row a tab stop and exposes selection state', async () => {
    const songs = h.factory('song').make(3)
    renderComponent(songs)
    await h.tick()

    expect(screen.getByRole('listbox').getAttribute('aria-multiselectable')).toBe('true')
    expect(tabbableRowIds()).toEqual([songs[0].id])

    await selectSongsAt(1)

    expect(screen.getAllByRole('option').map(row => row.getAttribute('aria-selected'))).toEqual([
      'false',
      'true',
      'false',
    ])
  })

  it('moves the focus and the tab stop with the arrow keys', async () => {
    const songs = h.factory('song').make(3)
    renderComponent(songs)
    screen.getAllByRole('option')[0].focus()

    await pressKey('{ArrowDown}')
    await pressKey('{ArrowDown}')
    await pressKey('{ArrowDown}')

    expect(document.activeElement).toBe(screen.getAllByRole('option')[2])
    expect(tabbableRowIds()).toEqual([songs[2].id])

    await pressKey('{ArrowUp}')

    expect(document.activeElement).toBe(screen.getAllByRole('option')[1])
  })

  it('makes a clicked row the tab stop', async () => {
    const songs = h.factory('song').make(3)
    renderComponent(songs)

    await h.user.click(screen.getAllByRole('option')[2])

    expect(tabbableRowIds()).toEqual([songs[2].id])
  })

  it('toggles the selection of the focused row with Space', async () => {
    const songs = h.factory('song').make(3)
    const { selectedPlayables } = renderComponent(songs)
    screen.getAllByRole('option')[1].focus()

    await pressKey(' ')

    expect(selectedIds(selectedPlayables)).toEqual([songs[1].id])

    await pressKey(' ')

    expect(selectedPlayables.value).toEqual([])
  })

  it('extends the selection with Shift and the arrow keys', async () => {
    const songs = h.factory('song').make(4)
    const { selectedPlayables } = renderComponent(songs)
    screen.getAllByRole('option')[1].focus()

    await pressKey('{Shift>}{ArrowDown}{ArrowDown}{/Shift}')

    expect(selectedIds(selectedPlayables)).toEqual([songs[1].id, songs[2].id, songs[3].id].sort())
  })

  it('jumps to the last and first rows with End and Home, rendering rows that were scrolled away', async () => {
    const songs = h.factory('song').make(30)
    renderComponent(songs)

    expect(screen.queryByRole('option', { name: new RegExp(songs[29].title) })).toBeNull()

    screen.getAllByRole('option')[0].focus()
    await pressKey('{End}')

    expect((document.activeElement as HTMLElement).dataset.playableId).toBe(songs[29].id)
    expect(tabbableRowIds()).toEqual([songs[29].id])

    await pressKey('{Home}')

    expect((document.activeElement as HTMLElement).dataset.playableId).toBe(songs[0].id)
  })

  it('scrolls a focused row that is out of view into view', async () => {
    const songs = h.factory('song').make(3)
    renderComponent(songs)

    const scroller = screen.getByTestId('song-list').querySelector<HTMLElement>('.virtual-scroller')!
    const rowTop = ref(400)

    vi.spyOn(Element.prototype, 'getBoundingClientRect').mockImplementation(function (this: Element) {
      return this === scroller
        ? DOMRect.fromRect({ y: 0, height: 300 })
        : DOMRect.fromRect({ y: rowTop.value, height: 64 })
    })

    screen.getAllByRole('option')[0].focus()
    await pressKey('{ArrowDown}')

    expect(scroller.scrollTop).toBe(164)

    rowTop.value = -100
    await pressKey('{ArrowUp}')

    expect(scroller.scrollTop).toBe(64)
  })

  it('keeps a tab stop on a rendered row when the focused row is scrolled away', async () => {
    const songs = h.factory('song').make(30)
    renderComponent(songs)
    screen.getAllByRole('option')[0].focus()

    const scroller = screen.getByTestId('song-list').querySelector<HTMLElement>('.virtual-scroller')!
    scroller.scrollTop = 64 * 20
    await fireEvent.scroll(scroller)

    await vi.waitFor(() => expect(renderedRowIds()).not.toContain(songs[0].id))
    await h.tick()

    expect(tabbableRowIds()).toEqual([renderedRowIds()[0]])
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
