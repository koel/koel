<template>
  <main class="relative flex flex-col overflow-auto flex-1">
    <div class="track-list-wrap relative flex flex-col flex-1 overflow-auto">
      <VirtualScroller v-slot="{ item }: { item: PlayableRow }" :item-height="64" :items="rows">
        <TrackItem :key="item.playable.id" :item :preview @play="emit('play', item.playable)" />
      </VirtualScroller>
    </div>
  </main>
</template>

<script setup lang="ts">
import { computed, reactive } from 'vue'

import VirtualScroller from '@/components/ui/VirtualScroller.vue'
import TrackItem from '@/components/embed/widget/EmbedWidgetTrackItem.vue'

const props = defineProps<{ playables: Playable[]; preview: boolean }>()
const emit = defineEmits<{ (e: 'play', playable: Playable): void }>()

const { playables, preview } = props

const rows = computed(() => {
  return playables.map<PlayableRow>(playable =>
    reactive({
      playable,
      selected: false,
    }),
  )
})
</script>

<style scoped lang="postcss">
@reference '@css/app.pcss';
.track-list-wrap {
  .virtual-scroller {
    @apply flex-1;
  }
}
</style>
