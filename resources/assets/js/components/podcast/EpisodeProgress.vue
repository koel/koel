<template>
  <div class="relative h-1 w-full rounded-full overflow-hidden bg-white/30">
    <span class="absolute h-full bg-k-accent top-0 left-0" :style="{ width: `${percentage}%` }" />
  </div>
</template>

<script setup lang="ts">
import { computed, toRefs } from 'vue'

const props = defineProps<{ episode: Episode, position: number }>()
const { episode, position } = toRefs(props)

const percentage = computed(() => (position.value / episode.value.length) * 100)
</script>
