<template>
  <main class="relative flex flex-col overflow-scroll flex-1">
    <div class="track-list-wrap relative flex flex-col flex-1 overflow-auto p-2">
      <VirtualScroller
        v-slot="{ item }: { item: PlayableRow }"
        :item-height="64"
        :items="rows"
      >
        <TrackItem :key="item.playable.id" :item @play="emit('play', item.playable)" />
      </VirtualScroller>
    </div>
  </main>
</template>

<script setup lang="ts">
import { computed, reactive } from 'vue'

import VirtualScroller from '@/components/ui/VirtualScroller.vue'
import TrackItem from '@/components/embed/widget/EmbedWidgetTrackItem.vue'

const props = defineProps<{ playables: Playable[] }>()
const emit = defineEmits<{ (e: 'play', playable: Playable): void }>()

const { playables } = props

const rows = computed(() => {
  return playables.map<PlayableRow>(playable => reactive({
    playable,
    selected: false,
  }))
})
</script>

<style scoped lang="postcss">
.track-list-wrap {
  .virtual-scroller {
    @apply flex-1;
  }
}
</style>
