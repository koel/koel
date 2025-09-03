<template>
  <div class="relative" data-testid="song-list-controls">
    <div class="flex gap-2 flex-wrap">
      <BtnGroup uppercase>
        <template v-if="altPressed">
          <Btn
            v-if="selectedPlayables.length < 2 && filteredPlayables.length"
            v-koel-tooltip
            class="btn-play-all"
            highlight
            title="Play all. Press Alt/⌥ to change mode."
            @click.prevent="playAll"
          >
            <Icon :icon="faPlay" fixed-width />
            All
          </Btn>

          <Btn
            v-if="selectedPlayables.length > 1"
            v-koel-tooltip
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
            v-if="selectedPlayables.length < 2 && filteredPlayables.length"
            v-koel-tooltip
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
            v-if="selectedPlayables.length > 1"
            v-koel-tooltip
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

      <BtnGroup v-if="config.refresh">
        <Btn v-if="config.refresh" v-koel-tooltip success title="Refresh" @click.prevent="refresh">
          <Icon :icon="faRotateRight" fixed-width />
        </Btn>
      </BtnGroup>

      <BtnGroup v-if="config.filter && allPlayables.length">
        <ListFilter />
      </BtnGroup>

      <slot />
    </div>

    <OnClickOutside @trigger="closeAddToMenu">
      <div ref="addToMenu" class="context-menu p-0 hidden">
        <AddToMenu :config="config.addTo" :playables="selectedPlayables" @closing="closeAddToMenu" />
      </div>
    </OnClickOutside>
  </div>
</template>

<script lang="ts" setup>
import { faPlay, faRandom, faRotateRight } from '@fortawesome/free-solid-svg-icons'
import type { Ref } from 'vue'
import { computed, defineAsyncComponent, nextTick, onBeforeUnmount, onMounted, ref, toRef, watch } from 'vue'
import { OnClickOutside } from '@vueuse/components'
import { FilteredPlayablesKey, PlayablesKey, SelectedPlayablesKey } from '@/symbols'
import { requireInjection } from '@/utils/helpers'
import { useFloatingUi } from '@/composables/useFloatingUi'

import AddToMenu from '@/components/playable/AddToMenu.vue'
import Btn from '@/components/ui/form/Btn.vue'
import BtnGroup from '@/components/ui/form/BtnGroup.vue'

const props = defineProps<{ config: PlayableListControlsConfig }>()

const emit = defineEmits<{
  (e: 'play-all' | 'play-selected', shuffle: boolean): void
  (e: 'filter', keywords: string): void
  (e: 'clear-queue' | 'delete-playlist' | 'refresh'): void
}>()

const ListFilter = defineAsyncComponent(() => import('@/components/ui/ListFilter.vue'))

const config = toRef(props, 'config')

const [allPlayables] = requireInjection<[Ref<Playable[]>]>(PlayablesKey)
const [filteredPlayables] = requireInjection<[Ref<Playable[]>]>(FilteredPlayablesKey)
const [selectedPlayables] = requireInjection<[Ref<Playable[]>]>(SelectedPlayablesKey)

const addToButton = ref<InstanceType<typeof Btn>>()
const addToMenu = ref<HTMLDivElement>()
const showingAddToMenu = ref(false)
const altPressed = ref(false)

const showAddToButton = computed(() => Boolean(selectedPlayables.value.length))

const shuffle = () => emit('play-all', true)
const shuffleSelected = () => emit('play-selected', true)
const playAll = () => emit('play-all', false)
const playSelected = () => emit('play-selected', false)
const clearQueue = () => emit('clear-queue')
const refresh = () => emit('refresh')
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
