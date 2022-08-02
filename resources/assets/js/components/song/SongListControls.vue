<template>
  <div class="song-list-controls" data-testid="song-list-controls" ref="el">
    <BtnGroup uppercased>
      <template v-if="mergedConfig.play">
        <template v-if="altPressed">
          <Btn
            v-if="selectedSongs.length < 2 && songs.length"
            class="btn-play-all"
            orange
            title="Play all songs"
            @click.prevent="playAll"
          >
            <icon :icon="faPlay"/>
            All
          </Btn>

          <Btn
            v-if="selectedSongs.length > 1"
            class="btn-play-selected"
            orange
            title="Play selected songs"
            @click.prevent="playSelected"
          >
            <icon :icon="faPlay"/>
            Selected
          </Btn>
        </template>

        <template v-else>
          <Btn
            v-if="selectedSongs.length < 2 && songs.length"
            class="btn-shuffle-all"
            data-testid="btn-shuffle-all"
            orange
            title="Shuffle all songs"
            @click.prevent="shuffle"
          >
            <icon :icon="faRandom"/>
            All
          </Btn>

          <Btn
            v-if="selectedSongs.length > 1"
            class="btn-shuffle-selected"
            data-testid="btn-shuffle-selected"
            orange
            title="Shuffle selected songs"
            @click.prevent="shuffleSelected"
          >
            <icon :icon="faRandom"/>
            Selected
          </Btn>
        </template>
      </template>

      <Btn
        v-if="selectedSongs.length"
        :title="`${showingAddToMenu ? 'Cancel' : 'Add selected songs to…'}`"
        class="btn-add-to"
        data-testid="add-to-btn"
        green
        @click.prevent.stop="toggleAddToMenu"
      >
        {{ showingAddToMenu ? 'Cancel' : 'Add To…' }}
      </Btn>

      <Btn v-if="showClearQueueButton" red title="Clear current queue" @click.prevent="clearQueue">Clear</Btn>

      <Btn
        v-if="showDeletePlaylistButton"
        class="del btn-delete-playlist"
        red
        title="Delete this playlist"
        @click.prevent="deletePlaylist"
      >
        <icon :icon="faTimes"/>
        Playlist
      </Btn>

    </BtnGroup>

    <AddToMenu
      v-koel-clickaway="closeAddToMenu"
      :config="mergedConfig.addTo"
      :showing="showingAddToMenu"
      :songs="selectedSongs"
      @closing="closeAddToMenu"
    />
  </div>
</template>

<script lang="ts" setup>
import { faPlay, faRandom, faTimes } from '@fortawesome/free-solid-svg-icons'
import { computed, nextTick, onMounted, onUnmounted, ref, toRefs } from 'vue'
import { SelectedSongsKey, SongsKey } from '@/symbols'
import { requireInjection } from '@/utils'

import AddToMenu from '@/components/song/AddToMenu.vue'
import Btn from '@/components/ui/Btn.vue'
import BtnGroup from '@/components/ui/BtnGroup.vue'

const props = withDefaults(defineProps<{ config?: Partial<SongListControlsConfig> }>(), { config: () => ({}) })
const { config } = toRefs(props)

const [songs] = requireInjection(SongsKey)
const [selectedSongs] = requireInjection(SelectedSongsKey)

const el = ref<HTMLElement>()
const showingAddToMenu = ref(false)
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
  }, config.value)
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
const registerKeydown = (event: KeyboardEvent) => event.key === 'Alt' && (altPressed.value = true)
const registerKeyup = (event: KeyboardEvent) => event.key === 'Alt' && (altPressed.value = false)

const toggleAddToMenu = async () => {
  showingAddToMenu.value = !showingAddToMenu.value

  if (!showingAddToMenu.value) {
    return
  }

  await nextTick()

  const btnAddTo = el.value?.querySelector<HTMLButtonElement>('.btn-add-to')!
  const { left: btnLeft, bottom: btnBottom, width: btnWidth } = btnAddTo.getBoundingClientRect()
  const contextMenu = el.value?.querySelector<HTMLElement>('.add-to')!
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
