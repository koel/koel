<template>
  <div class="song-list-controls" data-test="song-list-controls" ref="el">
    <BtnGroup uppercased>
      <template v-if="mergedConfig.play">
        <template v-if="altPressed">
          <Btn
            @click.prevent="playAll"
            class="btn-play-all"
            orange
            title="Play all songs"
            v-if="selectedSongs.length < 2 && songs.length"
            data-test="btn-play-all"
          >
            <i class="fa fa-play"></i> All
          </Btn>

          <Btn
            @click.prevent="playSelected"
            class="btn-play-selected"
            orange
            title="Play selected songs"
            v-if="selectedSongs.length > 1"
            data-test="btn-play-selected"
          >
            <i class="fa fa-play"></i> Selected
          </Btn>
        </template>

        <template v-else>
          <Btn
            @click.prevent="shuffle"
            class="btn-shuffle-all"
            orange
            title="Shuffle all songs"
            v-if="selectedSongs.length < 2 && songs.length"
            data-test="btn-shuffle-all"
          >
            <i class="fa fa-random"></i> All
          </Btn>

          <Btn
            @click.prevent="shuffleSelected"
            class="btn-shuffle-selected"
            orange
            title="Shuffle selected songs"
            v-if="selectedSongs.length > 1"
            data-test="btn-shuffle-selected"
          >
            <i class="fa fa-random"></i> Selected
          </Btn>
        </template>
      </template>

      <Btn
        :title="`${showingAddToMenu ? 'Cancel' : 'Add selected songs to…'}`"
        @click.prevent.stop="toggleAddToMenu"
        class="btn-add-to"
        green
        v-if="selectedSongs.length"
        data-test="add-to-btn"
      >
        {{ showingAddToMenu ? 'Cancel' : 'Add To…' }}
      </Btn>

      <Btn
        @click.prevent="clearQueue"
        class="btn-clear-queue"
        red
        v-if="showClearQueueButton"
        title="Clear current queue"
      >
        Clear
      </Btn>

      <Btn
        @click.prevent="deletePlaylist"
        class="del btn-delete-playlist"
        red
        title="Delete this playlist"
        v-if="showDeletePlaylistButton"
      >
        <i class="fa fa-times"></i> Playlist
      </Btn>

    </BtnGroup>

    <AddToMenu
      @closing="closeAddToMenu"
      :config="mergedConfig.addTo"
      :songs="selectedSongs"
      :showing="showingAddToMenu"
      v-koel-clickaway="closeAddToMenu"
    />
  </div>
</template>

<script lang="ts" setup>
import { computed, defineAsyncComponent, nextTick, onMounted, onUnmounted, ref, toRefs } from 'vue'

const AddToMenu = defineAsyncComponent(() => import('./add-to-menu.vue'))
const Btn = defineAsyncComponent(() => import('@/components/ui/btn.vue'))
const BtnGroup = defineAsyncComponent(() => import('@/components/ui/btn-group.vue'))

const props = withDefaults(defineProps<{ songs: Song[], selectedSongs: Song[], config: Partial<SongListControlsConfig> }>(), {
  songs: () => [],
  selectedSongs: () => [],
  config: () => ({})
})

const { config, songs, selectedSongs } = toRefs(props)

const el = ref(null as unknown as HTMLElement)
const showingAddToMenu = ref(false)
const numberOfQueuedSongs = ref(0)
const altPressed = ref(false)

const mergedConfig = computed((): SongListControlsConfig => Object.assign({
    play: true,
    addTo: {
      queue: true,
      favorites: true,
      playlists: true,
      newPlaylist: true
    },
    clearQueue: false,
    deletePlaylist: false
  }, config)
)

const showClearQueueButton = computed(() => mergedConfig.value.clearQueue)
const showDeletePlaylistButton = computed(() => mergedConfig.value.deletePlaylist)

const emit = defineEmits(['playAll', 'playSelected', 'clearQueue', 'deletePlaylist'])

const shuffle = () => emit('playAll', true)
const shuffleSelected = () => emit('playSelected', true)
const playAll = () => emit('playAll', false)
const playSelected = () => emit('playSelected', false)
const clearQueue = () => emit('clearQueue')
const deletePlaylist = () => emit('deletePlaylist')
const closeAddToMenu = () => (showingAddToMenu.value = false)
const registerKeydown = (event: KeyboardEvent) => event.altKey && (altPressed.value = true)
const registerKeyup = (event: KeyboardEvent) => event.altKey && (altPressed.value = false)

const toggleAddToMenu = async () => {
  showingAddToMenu.value = !showingAddToMenu.value

  if (!showingAddToMenu.value) {
    return
  }

  await nextTick()

  const btnAddTo = el.value.querySelector<HTMLButtonElement>('.btn-add-to')!
  const { left: btnLeft, bottom: btnBottom, width: btnWidth } = btnAddTo.getBoundingClientRect()
  const contextMenu = el.value.querySelector<HTMLElement>('.add-to')!
  const menuWidth = contextMenu.getBoundingClientRect().width
  contextMenu.style.top = `${btnBottom + 10}px`
  contextMenu.style.left = `${btnLeft + btnWidth / 2 - menuWidth / 2}px`
}

onMounted(() => {
  window.addEventListener('keydown', registerKeydown)
  window.addEventListener('keyup', registerKeyup)
})

onUnmounted(() => {
  window.removeEventListener('keydown', registerKeydown)
  window.removeEventListener('keyup', registerKeyup)
})
</script>

<style lang="scss" scoped>
.song-list-controls {
  position: relative;
}
</style>
