import { describe, expect, it } from 'vite-plus/test'
import { ref } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import { useLocalStorage } from '@/composables/useLocalStorage'
import { usePersistedSort } from './usePersistedSort'

describe('usePersistedSort', () => {
  createHarness({
    beforeEach: () => localStorage.clear(),
  })

  it('falls back to the default field and ascending order when nothing is saved', () => {
    const { getSavedSort } = usePersistedSort('album', 'track')

    expect(getSavedSort()).toEqual({ sortField: 'track', sortOrder: 'asc' })
  })

  it('restores the saved sort', () => {
    const { set } = useLocalStorage()
    set('album-sort-field', ['artist_name', 'title'])
    set('album-sort-order', 'desc')

    const { getSavedSort } = usePersistedSort('album', 'track')

    expect(getSavedSort()).toEqual({ sortField: ['artist_name', 'title'], sortOrder: 'desc' })
  })

  it('saves the sort under the existing keys', () => {
    const { saveSort } = usePersistedSort('genre', 'title')

    saveSort('album_name', 'desc')

    const { get } = useLocalStorage()
    expect(get('genre-sort-field')).toBe('album_name')
    expect(get('genre-sort-order')).toBe('desc')
  })

  it('reads and writes under the current key of a dynamic prefix', () => {
    const playlistId = ref('first')
    const { getSavedSort, saveSort } = usePersistedSort(() => `playlist-${playlistId.value}`, null)

    saveSort('position', 'desc')
    playlistId.value = 'second'

    expect(getSavedSort()).toEqual({ sortField: null, sortOrder: 'asc' })

    playlistId.value = 'first'

    expect(getSavedSort()).toEqual({ sortField: 'position', sortOrder: 'desc' })
  })
})
