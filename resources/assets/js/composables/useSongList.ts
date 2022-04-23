/**
 * Add necessary functionalities into a view that contains a song-list component.
 */
import { ComponentInternalInstance, computed, getCurrentInstance, reactive, Ref, ref, watchEffect } from 'vue'
import isMobile from 'ismobilejs'

import { playback } from '@/services'
import { eventBus } from '@/utils'

import ControlsToggler from '@/components/ui/ScreenControlsToggler.vue'
import SongList from '@/components/song/SongList.vue'
import SongListControls from '@/components/song/SongListControls.vue'
import { queueStore, songStore } from '@/stores'
import router from '@/router'

export const useSongList = (songs: Ref<Song[]>, controlsConfig: Partial<SongListControlsConfig> = {}) => {
  const vm = getCurrentInstance()
  const songList = ref<InstanceType<typeof SongList>>()

  const isPhone = isMobile.phone
  const selectedSongs = ref<Song[]>([])
  const showingControls = ref(false)
  const songListControlConfig = reactive(controlsConfig)

  const duration = computed(() => songStore.getFormattedLength(songs.value))

  const getSongsToPlay = (): Song[] => songList.value.getAllSongsWithSort()
  const playAll = (shuffle: boolean) => playback.queueAndPlay(getSongsToPlay(), shuffle)
  const playSelected = (shuffle: boolean) => playback.queueAndPlay(selectedSongs.value, shuffle)
  const toggleControls = () => (showingControls.value = !showingControls.value)

  const onPressEnter = async (event: KeyboardEvent) => {
    if (selectedSongs.value.length === 1) {
      queueStore.queueIfNotQueued(selectedSongs.value[0])
      await playback.play(selectedSongs.value[0])
      return
    }

    //  • Only Enter: Queue songs to bottom
    //  • Shift+Enter: Queues song to top
    //  • Cmd/Ctrl+Enter: Queues song to bottom and play the first selected song
    //  • Cmd/Ctrl+Shift+Enter: Queue songs to top and play the first queued song
    event.shiftKey ? queueStore.queueToTop(selectedSongs.value) : queueStore.queue(selectedSongs.value)

    if (event.ctrlKey || event.metaKey) {
      await playback.play(selectedSongs.value[0])
    }

    router.go('/queue')
  }

  eventBus.on({
    SET_SELECTED_SONGS (songs: Song[], target: ComponentInternalInstance) {
      target === vm && (selectedSongs.value = songs)
    }
  })

  return {
    SongList,
    SongListControls,
    ControlsToggler,
    songs,
    duration,
    songList,
    selectedSongs,
    showingControls,
    songListControlConfig,
    isPhone,
    onPressEnter,
    playAll,
    playSelected,
    toggleControls
  }
}
