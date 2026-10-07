import { describe, expect, it } from 'vite-plus/test'
import { defineComponent, ref } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import { usePlayableList } from './usePlayableList'

describe('usePlayableList', () => {
  const h = createHarness()

  it('keeps the track order of a compilation when sorting by album', () => {
    const albumId = 'compilation'

    const first = h.factory('song').make({
      album_id: albumId,
      album_name: 'Hits',
      artist_name: 'Zappa',
      disc: 1,
      track: 1,
    })

    const second = h.factory('song').make({
      album_id: albumId,
      album_name: 'Hits',
      artist_name: 'ABBA',
      disc: 1,
      track: 2,
    })

    let playableList!: ReturnType<typeof usePlayableList>

    h.render(
      defineComponent({
        setup: () => {
          playableList = usePlayableList(ref([second, first]))

          return () => null
        },
      }),
    )

    playableList.sortField.value = 'album_name'

    expect(playableList.filteredPlayables.value.map(playable => playable.id)).toEqual([first.id, second.id])
  })
})
