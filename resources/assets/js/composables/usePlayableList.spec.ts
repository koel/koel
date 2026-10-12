import { describe, expect, it } from 'vite-plus/test'
import type { Ref } from 'vue'
import { defineComponent, ref } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import { eventBus } from '@/utils/eventBus'
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
  const renderWithPlayables = (playables: Ref<Playable[]>) =>
    h.render(
      defineComponent({
        setup: () => {
          usePlayableList(playables)

          return () => null
        },
      }),
    )

  it('removes deleted songs from the list', () => {
    const [kept, deleted] = h.factory('song').make(2)
    const playables = ref<Playable[]>([kept, deleted])

    renderWithPlayables(playables)

    eventBus.emit('SONGS_DELETED', [deleted])

    expect(playables.value).toEqual([kept])
  })

  it('stops removing deleted songs once unmounted', () => {
    const songs = h.factory('song').make(2)
    const playables = ref<Playable[]>([...songs])

    renderWithPlayables(playables).unmount()

    eventBus.emit('SONGS_DELETED', [songs[0]])

    expect(playables.value).toEqual(songs)
  })
})
