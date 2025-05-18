<template>
  <article class="h-[40px] overflow-hidden border-b border-white/10" :class="{ playing }">
    <div
      class="flex gap-2 text-k-text-secondary px-6 py-3 cursor-default user-select-none"
      :class="item.type === 'folders' && 'items-center'"
    >
      <template v-if="item.type === 'songs'">
        <span v-if="item.playback_state === 'Playing'" class="pl-[3px]">
          <SoundBars />
        </span>
        <FileMusicIcon v-else :size="16" />
      </template>
      <Icon v-else :icon="faFolder" class="text-k-text-primary" fixed-width />
      <span class="flex-1 overflow-hidden text-ellipsis whitespace-nowrap user-select-none">{{ label }}</span>
    </div>
  </article>
</template>

<script setup lang="ts">
import { FileMusicIcon } from 'lucide-vue-next'
import { faFolder } from '@fortawesome/free-solid-svg-icons'
import { computed, toRefs } from 'vue'
import { isSong } from '@/utils/typeGuards'

import SoundBars from '@/components/ui/SoundBars.vue'

const props = defineProps<{ item: Song | Folder }>()
const { item } = toRefs(props)

const playing = computed(
  () => isSong(item.value) && ['Playing', 'Paused'].includes(item.value.playback_state!),
)

const label = computed(() => {
  return isSong(item.value)
    ? `${item.value.artist_name} - ${item.value.album_name} - ${item.value.title}`
    : item.value.name
})
</script>

<style scoped lang="postcss">
.selected {
  @apply bg-white/10 border-transparent;
}

.playing,
.playing * {
  @apply text-k-highlight;
}
</style>
