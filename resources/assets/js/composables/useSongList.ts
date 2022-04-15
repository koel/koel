/**
 * Add necessary functionalities into a view that contains a song-list component.
 */
import { ComponentInternalInstance, getCurrentInstance, reactive, ref, watchEffect } from 'vue'
import isMobile from 'ismobilejs'

import { playback } from '@/services'
import { eventBus } from '@/utils'

import ControlsToggler from '@/components/ui/screen-controls-toggler.vue'
import SongList from '@/components/song/list.vue'
import SongListControls from '@/components/song/list-controls.vue'
import { songStore } from '@/stores'

export const useSongList = (controlsConfig: Partial<SongListControlsConfig> = {}) => {
  const songList = ref(null)
  const state = reactive<SongListState>({ songs: [] })

  const meta = reactive<SongListMeta>({
    songCount: 0,
    totalLength: '00:00'
  })

  const selectedSongs = ref<Song[]>([])
  const showingControls = ref(false)
  const songListControlConfig = reactive(controlsConfig)
  const isPhone = isMobile.phone

  watchEffect(() => {
    if (!state.songs.length) {
      return
    }

    meta.songCount = state.songs.length
    meta.totalLength = songStore.getFormattedLength(state.songs)
  })

  const getSongsToPlay = (): Song[] => (songList.value as any).getAllSongsWithSort()
  const playAll = (shuffled: boolean) => playback.queueAndPlay(getSongsToPlay(), shuffled)
  const playSelected = (shuffled: boolean) => playback.queueAndPlay(selectedSongs.value, shuffled)
  const toggleControls = () => (showingControls.value = !showingControls.value)

  eventBus.on({
    SET_SELECTED_SONGS (songs: Song[], target: ComponentInternalInstance) {
      target === getCurrentInstance() && (selectedSongs.value = songs)
    }
  })

  return {
    SongList,
    SongListControls,
    ControlsToggler,
    songList,
    state,
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
