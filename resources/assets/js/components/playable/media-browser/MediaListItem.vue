<template>
  <article class="h-[40px] overflow-hidden border-b border-white/10" :class="{ playing }">
    <div
      class="flex gap-2 text-k-text-secondary px-6 py-3 cursor-default user-select-none"
      :class="item.type === 'folders' && 'items-center'"
    >
      <template v-if="item.type === 'songs'">
        <span v-if="item.playback_state === 'Playing'" class="pl-[3px]" @click="pausePlayback">
          <SoundBars />
        </span>
        <template v-else>
          <FileMusicIcon class="hidden sm:block" :size="16" />

          <!-- on a mobile device, show a Play button for a better UX -->
          <button
            v-if="item.playback_state !== 'Playing'"
            class="sm:hidden py-px"
            title="Play"
            @click.prevent.stop="emit('play-song')"
          >
            <PlayCircleIcon :size="16" />
          </button>
        </template>
      </template>
      <Icon v-else :icon="faFolder" class="text-k-text-primary" fixed-width />
      <span class="flex-1 overflow-hidden text-ellipsis whitespace-nowrap user-select-none">{{ label }}</span>

      <!-- on a mobile device, show an Open button for a beter UX -->
      <button
        v-if="item.type === 'folders'"
        class="sm:hidden border border-white/10 rounded px-1.5 py-px"
        title="Open"
        @click.prevent.stop="emit('open-folder')"
      >
        Open
      </button>
    </div>
  </article>
</template>

<script setup lang="ts">
import { FileMusicIcon, PlayCircleIcon } from 'lucide-vue-next'
import { faFolder } from '@fortawesome/free-solid-svg-icons'
import { computed, toRefs } from 'vue'
import { isSong } from '@/utils/typeGuards'
import { playback } from '@/services/playbackManager'

import SoundBars from '@/components/ui/SoundBars.vue'

const props = defineProps<{ item: Song | Folder }>()
const emit = defineEmits<{
  (e: 'play-song'): void
  (e: 'open-folder'): void
}>()

const { item } = toRefs(props)

const playing = computed(
  () => isSong(item.value) && ['Playing', 'Paused'].includes(item.value.playback_state!),
)

const label = computed(() => isSong(item.value) ? item.value.basename : item.value.name)

const pausePlayback = () => playback().pause()
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
