<template>
  <div class="relative" data-testid="song-list-controls">
    <div class="flex gap-2 flex-wrap">
      <BtnGroup uppercased>
        <template v-if="altPressed">
          <Btn
            v-if="selectedSongs.length < 2 && songs.length"
            v-koel-tooltip.bottom
            class="btn-play-all"
            highlight
            title="Play all. Press Alt/⌥ to change mode."
            @click.prevent="playAll"
          >
            <Icon :icon="faPlay" fixed-width />
            All
          </Btn>

          <Btn
            v-if="selectedSongs.length > 1"
            v-koel-tooltip.bottom
            class="btn-play-selected"
            highlight
            title="Play selected. Press Alt/⌥ to change mode."
            @click.prevent="playSelected"
          >
            <Icon :icon="faPlay" fixed-width />
            Selected
          </Btn>
        </template>

        <template v-else>
          <Btn
            v-if="selectedSongs.length < 2 && songs.length"
            v-koel-tooltip.bottom
            class="btn-shuffle-all"
            data-testid="btn-shuffle-all"
            highlight
            title="Shuffle all. Press Alt/⌥ to change mode."
            @click.prevent="shuffle"
          >
            <Icon :icon="faRandom" fixed-width />
            All
          </Btn>

          <Btn
            v-if="selectedSongs.length > 1"
            v-koel-tooltip.bottom
            class="btn-shuffle-selected"
            data-testid="btn-shuffle-selected"
            highlight
            title="Shuffle selected. Press Alt/⌥ to change mode."
            @click.prevent="shuffleSelected"
          >
            <Icon :icon="faRandom" fixed-width />
            Selected
          </Btn>
        </template>

        <Btn
          v-if="showAddToButton"
          ref="addToButton"
          success
          @click.prevent.stop="toggleAddToMenu"
        >
          {{ showingAddToMenu ? 'Cancel' : 'Add To…' }}
        </Btn>

        <Btn v-if="config.clearQueue" danger title="Clear current queue" @click.prevent="clearQueue">Clear</Btn>
      </BtnGroup>

      <BtnGroup v-if="config.refresh || config.deletePlaylist">
        <Btn v-if="config.refresh" v-koel-tooltip success title="Refresh" @click.prevent="refresh">
          <Icon :icon="faRotateRight" fixed-width />
        </Btn>

        <Btn
          v-if="config.deletePlaylist"
          v-koel-tooltip
          class="del btn-delete-playlist"
          danger
          title="Delete this playlist"
          @click.prevent="deletePlaylist"
        >
          <Icon :icon="faTrashCan" />
        </Btn>
      </BtnGroup>

      <BtnGroup v-if="config.filter && songs.length">
        <SongListFilter @change="filter" />
      </BtnGroup>
    </div>

    <div ref="addToMenu" v-koel-clickaway="closeAddToMenu" class="context-menu p-0 hidden">
      <AddToMenu :config="config.addTo" :songs="selectedSongs" @closing="closeAddToMenu" />
    </div>
  </div>
</template>

<script lang="ts" setup>
import { faPlay, faRandom, faRotateRight, faTrashCan } from '@fortawesome/free-solid-svg-icons'
import { computed, defineAsyncComponent, nextTick, onBeforeUnmount, onMounted, Ref, ref, toRef, watch } from 'vue'
import { SelectedSongsKey, SongsKey } from '@/symbols'
import { requireInjection } from '@/utils'
import { useFloatingUi } from '@/composables'

import AddToMenu from '@/components/song/AddToMenu.vue'
import Btn from '@/components/ui/form/Btn.vue'
import BtnGroup from '@/components/ui/form/BtnGroup.vue'

const SongListFilter = defineAsyncComponent(() => import('@/components/song/SongListFilter.vue'))

const props = defineProps<{ config: SongListControlsConfig }>()
const config = toRef(props, 'config')

const [songs] = requireInjection<[Ref<Song[]>]>(SongsKey)
const [selectedSongs] = requireInjection(SelectedSongsKey)

const addToButton = ref<InstanceType<typeof Btn>>()
const addToMenu = ref<HTMLDivElement>()
const showingAddToMenu = ref(false)
const altPressed = ref(false)

const showAddToButton = computed(() => Boolean(selectedSongs.value.length))

const emit = defineEmits<{
  (e: 'playAll' | 'playSelected', shuffle: boolean): void,
  (e: 'filter', keywords: string): void,
  (e: 'clearQueue' | 'deletePlaylist' | 'refresh'): void,
}>()

const shuffle = () => emit('playAll', true)
const shuffleSelected = () => emit('playSelected', true)
const playAll = () => emit('playAll', false)
const playSelected = () => emit('playSelected', false)
const clearQueue = () => emit('clearQueue')
const deletePlaylist = () => emit('deletePlaylist')
const refresh = () => emit('refresh')
const filter = (keywords: string) => emit('filter', keywords)
const registerKeydown = (event: KeyboardEvent) => event.key === 'Alt' && (altPressed.value = true)
const registerKeyup = (event: KeyboardEvent) => event.key === 'Alt' && (altPressed.value = false)

let usedFloatingUi: ReturnType<typeof useFloatingUi>

watch(showAddToButton, async showingButton => {
  await nextTick()

  if (showingButton) {
    usedFloatingUi = useFloatingUi(addToButton.value!.button!, addToMenu, { autoTrigger: false })
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
