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

  it('sorts by disc again when a one-disc list grows into two discs', () => {
    const discOneTrackOne = h.factory('song').make({ disc: 1, track: 1 })
    const discOneTrackTwo = h.factory('song').make({ disc: 1, track: 2 })
    const discTwoTrackOne = h.factory('song').make({ disc: 2, track: 1 })
    const playables = ref<Playable[]>([discOneTrackTwo, discOneTrackOne])

    let playableList!: ReturnType<typeof usePlayableList>

    h.render(
      defineComponent({
        setup: () => {
          playableList = usePlayableList(playables)

          return () => null
        },
      }),
    )

    playableList.sortField.value = 'track'
    expect(playableList.filteredPlayables.value).toHaveLength(2)

    playables.value = [discTwoTrackOne, discOneTrackTwo, discOneTrackOne]

    expect(playableList.filteredPlayables.value.map(playable => playable.id)).toEqual([
      discOneTrackOne.id,
      discOneTrackTwo.id,
      discTwoTrackOne.id,
    ])
  })

  it('keeps the server order of a list sorted on the server', () => {
    const lowercase = h.factory('song').make({ title: 'apple' })
    const uppercase = h.factory('song').make({ title: 'Banana' })
    const playables = ref<Playable[]>([lowercase])

    let playableList!: ReturnType<typeof usePlayableList>

    h.render(
      defineComponent({
        setup: () => {
          playableList = usePlayableList(playables, {}, { sortedOnServer: true })

          return () => null
        },
      }),
    )

    playableList.sort('title', 'asc')
    playables.value.push(uppercase)

    expect(playableList.filteredPlayables.value.map(playable => playable.id)).toEqual([lowercase.id, uppercase.id])
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
