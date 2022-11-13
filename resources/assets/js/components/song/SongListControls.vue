<template>
  <div ref="el" class="song-list-controls" data-testid="song-list-controls">
    <div class="wrapper">
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
              <icon :icon="faPlay" fixed-width/>
              All
            </Btn>

            <Btn
              v-if="selectedSongs.length > 1"
              class="btn-play-selected"
              orange
              title="Play selected songs"
              @click.prevent="playSelected"
            >
              <icon :icon="faPlay" fixed-width/>
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
              <icon :icon="faRandom" fixed-width/>
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
              <icon :icon="faRandom" fixed-width/>
              Selected
            </Btn>
          </template>
        </template>

        <Btn v-if="showAddToButton" ref="addToButton" green @click.prevent.stop="toggleAddToMenu">
          {{ showingAddToMenu ? 'Cancel' : 'Add To…' }}
        </Btn>

        <Btn v-if="showClearQueueButton" red title="Clear current queue" @click.prevent="clearQueue">Clear</Btn>
      </BtnGroup>

      <BtnGroup>
        <Btn v-if="config.refresh" v-koel-tooltip green title="Refresh" @click.prevent="refresh">
          <icon :icon="faRotateRight" fixed-width/>
        </Btn>

        <Btn
          v-if="showDeletePlaylistButton"
          v-koel-tooltip
          class="del btn-delete-playlist"
          red
          title="Delete this playlist"
          @click.prevent="deletePlaylist"
        >
          <icon :icon="faTrashCan"/>
        </Btn>
      </BtnGroup>
    </div>

    <div ref="addToMenu" v-koel-clickaway="closeAddToMenu" class="menu-wrapper">
      <AddToMenu :config="mergedConfig.addTo" :songs="selectedSongs" @closing="closeAddToMenu"/>
    </div>
  </div>
</template>

<script lang="ts" setup>
import { faPlay, faRandom, faRotateRight, faTrashCan } from '@fortawesome/free-solid-svg-icons'
import { computed, nextTick, onBeforeUnmount, onMounted, Ref, ref, toRefs, watch } from 'vue'
import { SelectedSongsKey, SongsKey } from '@/symbols'
import { requireInjection } from '@/utils'
import { useFloatingUi } from '@/composables'

import AddToMenu from '@/components/song/AddToMenu.vue'
import Btn from '@/components/ui/Btn.vue'
import BtnGroup from '@/components/ui/BtnGroup.vue'

const props = withDefaults(defineProps<{ config?: Partial<SongListControlsConfig> }>(), { config: () => ({}) })
const { config } = toRefs(props)

const [songs] = requireInjection<[Ref<Song[]>]>(SongsKey)
const [selectedSongs] = requireInjection(SelectedSongsKey)

const el = ref<HTMLElement>()
const addToButton = ref<InstanceType<Btn>>()
const addToMenu = ref<HTMLDivElement>()
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
    deletePlaylist: false,
    refresh: false
  }, config.value)
)

const showAddToButton = computed(() => Boolean(selectedSongs.value.length))
const showClearQueueButton = computed(() => mergedConfig.value.clearQueue)
const showDeletePlaylistButton = computed(() => mergedConfig.value.deletePlaylist)

const emit = defineEmits<{
  (e: 'playAll' | 'playSelected', shuffle: boolean): void,
  (e: 'clearQueue' | 'deletePlaylist' | 'refresh'): void,
}>()

const shuffle = () => emit('playAll', true)
const shuffleSelected = () => emit('playSelected', true)
const playAll = () => emit('playAll', false)
const playSelected = () => emit('playSelected', false)
const clearQueue = () => emit('clearQueue')
const deletePlaylist = () => emit('deletePlaylist')
const refresh = () => emit('refresh')
const registerKeydown = (event: KeyboardEvent) => event.key === 'Alt' && (altPressed.value = true)
const registerKeyup = (event: KeyboardEvent) => event.key === 'Alt' && (altPressed.value = false)

let usedFloatingUi: ReturnType<typeof useFloatingUi>

watch(showAddToButton, async showingButton => {
  await nextTick()

  if (showingButton) {
    usedFloatingUi = useFloatingUi(addToButton.value.button, addToMenu, { autoTrigger: false })
    usedFloatingUi.setup()
  } else {
    usedFloatingUi?.teardown()
  }
}, { immediate: true })

const closeAddToMenu = () => {
  usedFloatingUi?.hide()
  showingAddToMenu.value = false
}

const toggleAddToMenu = () => {
  showingAddToMenu.value ? usedFloatingUi?.hide() : usedFloatingUi?.show()
  showingAddToMenu.value = !showingAddToMenu.value
}

onMounted(() => {
  window.addEventListener('keydown', registerKeydown)
  window.addEventListener('keyup', registerKeyup)
})

onBeforeUnmount(() => {
  window.removeEventListener('keydown', registerKeydown)
  window.removeEventListener('keyup', registerKeyup)

  usedFloatingUi?.teardown()
})
</script>

<style lang="scss" scoped>
.song-list-controls {
  position: relative;

  .wrapper {
    display: flex;
    gap: .5rem;
  }

  .menu-wrapper {
    @include context-menu();

    padding: 0;
    display: none;
  }
}
</style>
