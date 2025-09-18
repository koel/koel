<template>
  <div
    ref="progressBar"
    :class="playable?.playback_state || 'invisible'"
    class="progress-bar group flex-1 h-8 flex justify-center flex-col relative cursor-pointer"
    tabindex="0"
    @click="onClick"
    @mousemove="onMouseMove"
    @mouseout="onMouseOut"
  >
    <div
      class="track w-full relative h-1 rounded-full bg-white/20
      before:h-full before:rounded-full before:bg-white/30 before:absolute before:top-0 before:left-0
      before:pointer-events-none"
    >
      <div
        class="seeker group-hover:bg-k-highlight relative h-1 rounded-full bg-white/50
        after:transition-transform after:absolute after:top-1/2 after:right-[-10px] after:size-[10px] after:bg-white after:rounded-full"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, toRefs } from 'vue'

const props = withDefaults(defineProps<{ playable?: Playable, progress?: number }>(), {
  progress: 0,
})

const emit = defineEmits<{ (e: 'seek', percentage: number): void }>()

const { playable, progress } = toRefs(props)

const progressBar = ref<HTMLDivElement>()
const trackHoverWidth = ref('0')

const progressPercentage = computed(() => `${progress.value}%`)

const onMouseOut = () => (trackHoverWidth.value = '0')

const onMouseMove = (e: MouseEvent) => {
  const rect = progressBar.value!.getBoundingClientRect()
  trackHoverWidth.value = `${(e.clientX - rect.left) / rect.width * 100}%`
}

const onClick = (e: MouseEvent) => {
  if (!playable.value || playable.value.playback_state === 'Stopped') {
    return
  }

  const rect = progressBar.value!.getBoundingClientRect()
  emit('seek', (e.clientX - rect.left) * 100 / rect.width)
}
</script>

<style scoped lang="postcss">
.progress-bar {
  &:hover {
    .seeker::after {
      transform: translate(-50%, -50%) scale(1.4);
    }
  }
}

.track::before {
  width: v-bind(trackHoverWidth);
}

.seeker {
  width: v-bind(progressPercentage);

  &::after {
    transform: translate(-50%, -50%);
  }
}
</style>
