<template>
  <div class="relative" data-testid="song-list-controls">
    <div class="flex gap-2 flex-wrap">
      <BtnGroup uppercase>
        <template v-if="altPressed">
          <Btn
            v-if="selectedPlayables.length < 2 && filteredPlayables.length"
            v-koel-tooltip
            variant="highlight"
            class="btn-play-all"
            title="Play all. Press Alt/⌥ to change mode."
            @click.prevent="playAll"
          >
            <Icon :icon="faPlay" fixed-width />
            All
          </Btn>

          <Btn
            v-if="selectedPlayables.length > 1"
            v-koel-tooltip
            variant="highlight"
            class="btn-play-selected"
            title="Play selected. Press Alt/⌥ to change mode."
            @click.prevent="playSelected"
          >
            <Icon :icon="faPlay" fixed-width />
            Selected
          </Btn>
        </template>

        <template v-else>
          <Btn
            v-if="selectedPlayables.length < 2 && filteredPlayables.length"
            v-koel-tooltip
            variant="highlight"
            class="btn-shuffle-all"
            data-testid="btn-shuffle-all"
            title="Shuffle all. Press Alt/⌥ to change mode."
            @click.prevent="shuffle"
          >
            <Icon :icon="faRandom" fixed-width />
            All
          </Btn>

          <Btn
            v-if="selectedPlayables.length > 1"
            v-koel-tooltip
            variant="highlight"
            class="btn-shuffle-selected"
            data-testid="btn-shuffle-selected"
            title="Shuffle selected. Press Alt/⌥ to change mode."
            @click.prevent="shuffleSelected"
          >
            <Icon :icon="faRandom" fixed-width />
            Selected
          </Btn>
        </template>

        <Btn variant="success" v-if="showAddToButton" ref="addToButton">
          {{ showingAddToMenu ? 'Cancel' : 'Add To…' }}
        </Btn>

        <Btn variant="destructive" v-if="config.clearQueue" title="Clear current queue" @click.prevent="clearQueue"
          >Clear</Btn
        >
      </BtnGroup>

      <BtnGroup v-if="config.refresh">
        <Btn variant="success" v-if="config.refresh" v-koel-tooltip title="Refresh" @click.prevent="refresh">
          <Icon :icon="faRotateRight" fixed-width />
        </Btn>
      </BtnGroup>

      <BtnGroup v-if="config.filter && allPlayables.length">
        <ListFilter />
      </BtnGroup>

      <slot />
    </div>

    <Popover
      v-if="showAddToButton"
      ref="popover"
      :anchor="addToButton?.button"
      class="context-menu p-0"
      @toggle="showingAddToMenu = $event"
    >
      <AddToMenu :config="config.addTo" :playables="selectedPlayables" @closing="popover?.hide()" />
    </Popover>
  </div>
</template>

<script lang="ts" setup>
import { faPlay, faRandom, faRotateRight } from '@fortawesome/free-solid-svg-icons'
import type { Ref } from 'vue'
import { computed, onBeforeUnmount, onMounted, ref, toRef, watch } from 'vue'
import { FilteredPlayablesKey, PlayablesKey, SelectedPlayablesKey } from '@/config/symbols'
import { requireInjection } from '@/utils/helpers'

import AddToMenu from '@/components/playable/AddToMenu.vue'
import Btn from '@/components/ui/form/Btn.vue'
import BtnGroup from '@/components/ui/form/BtnGroup.vue'
import ListFilter from '@/components/ui/ListFilter.vue'
import Popover from '@/components/ui/Popover.vue'

const props = defineProps<{ config: PlayableListControlsConfig }>()

const emit = defineEmits<{
  (e: 'play-all' | 'play-selected', shuffle: boolean): void
  (e: 'filter', keywords: string): void
  (e: 'clear-queue' | 'delete-playlist' | 'refresh'): void
}>()

const config = toRef(props, 'config')

const [allPlayables] = requireInjection<[Ref<Playable[]>]>(PlayablesKey)
const [filteredPlayables] = requireInjection<[Ref<Playable[]>]>(FilteredPlayablesKey)
const [selectedPlayables] = requireInjection<[Ref<Playable[]>]>(SelectedPlayablesKey)

const addToButton = ref<InstanceType<typeof Btn>>()
const popover = ref<InstanceType<typeof Popover>>()
const showingAddToMenu = ref(false)
const altPressed = ref(false)

const showAddToButton = computed(() => Boolean(selectedPlayables.value.length))

// When the AddTo trigger button disappears (no items selected), the Popover
// is unmounted via v-if without firing @toggle(false), so we reset the menu
// open-state flag explicitly. Otherwise the trigger's "Cancel" / "Add To…"
// label could be stuck on "Cancel" if items are reselected later.
watch(showAddToButton, visible => {
  if (!visible) {
    showingAddToMenu.value = false
  }
})

const shuffle = () => emit('play-all', true)
const shuffleSelected = () => emit('play-selected', true)
const playAll = () => emit('play-all', false)
const playSelected = () => emit('play-selected', false)
const clearQueue = () => emit('clear-queue')
const refresh = () => emit('refresh')
const registerKeydown = (event: KeyboardEvent) => event.key === 'Alt' && (altPressed.value = true)
const registerKeyup = (event: KeyboardEvent) => event.key === 'Alt' && (altPressed.value = false)

onMounted(() => {
  window.addEventListener('keydown', registerKeydown)
  window.addEventListener('keyup', registerKeyup)
})

onBeforeUnmount(() => {
  window.removeEventListener('keydown', registerKeydown)
  window.removeEventListener('keyup', registerKeyup)
})
</script>
