<template>
  <div
    :class="preview && 'preview-wrapper p-[2px] bg-k-fg-5'"
    class="rounded-full hover:scale-110 transition"
    data-testid="wrapper"
  >
    <button
      class="w-12 aspect-square rounded-full text-k-highlight-fg bg-k-highlight border border-k-bg"
      type="button"
      @click.prevent="emit('clicked')"
    >
      <Icon v-if="playing" :icon="faPause" data-testid="icon-pause" size="lg" />
      <Icon v-else :icon="faPlay" class="ml-0.5" data-testid="icon-play" size="lg" />
      <span class="sr-only">{{ playing ? 'Pause' : 'Play/Resume' }}</span>
    </button>
  </div>
</template>

<script setup lang="ts">
import { faPause, faPlay } from '@fortawesome/free-solid-svg-icons'
import { computed, toRefs } from 'vue'

const props = withDefaults(defineProps<{ playable?: Playable, preview?: boolean, progress?: number }>(), {
  preview: false,
  progress: 0,
})

const emit = defineEmits<{ (e: 'clicked'): void }>()

const { playable, preview, progress } = toRefs(props)

const progressPercentage = computed(() => `${progress.value}%`)

const playing = computed(() => playable.value?.playback_state === 'Playing')
</script>

<style lang="postcss" scoped>
.preview-wrapper {
  --bg-color: color-mix(in srgb, var(--color-highlight), transparent 60%);

  background-size: 100% 100%;
  background-position: 0 0;
  background-image: conic-gradient(
    from 0deg at 50% 50%,
    var(--bg-color) 0%,
    var(--bg-color) v-bind(progressPercentage),
    transparent v-bind(progressPercentage),
    transparent 100%
  );
}
</style>
