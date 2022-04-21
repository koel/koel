/**
 * Add necessary functionalities into a view that contains a song-list component.
 */
import { ComponentInternalInstance, getCurrentInstance, reactive, Ref, ref, watchEffect } from 'vue'
import isMobile from 'ismobilejs'

import { playback } from '@/services'
import { eventBus } from '@/utils'

import ControlsToggler from '@/components/ui/ScreenControlsToggler.vue'
import SongList from '@/components/song/SongList.vue'
import SongListControls from '@/components/song/SongListControls.vue'
import { songStore } from '@/stores'

export const useSongList = (songs: Ref<Song[]>, controlsConfig: Partial<SongListControlsConfig> = {}) => {
  const songList = ref<InstanceType<typeof SongList>>()
  const vm = getCurrentInstance()

  const meta = reactive<SongListMeta>({
    songCount: 0,
    totalLength: '00:00'
  })

  const selectedSongs = ref<Song[]>([])
  const showingControls = ref(false)
  const songListControlConfig = reactive(controlsConfig)
  const isPhone = isMobile.phone

  watchEffect(() => {
    if (!songs.value.length) {
      return
    }

    meta.songCount = songs.value.length
    meta.totalLength = songStore.getFormattedLength(songs.value)
  })

  const getSongsToPlay = (): Song[] => songList.value.getAllSongsWithSort()
  const playAll = (shuffled: boolean) => playback.queueAndPlay(getSongsToPlay(), shuffled)
  const playSelected = (shuffled: boolean) => playback.queueAndPlay(selectedSongs.value, shuffled)
  const toggleControls = () => (showingControls.value = !showingControls.value)

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
    songList,
    meta,
    selectedSongs,
    showingControls,
    songListControlConfig,
    isPhone,
    playAll,
    playSelected,
    toggleControls
  }
}
