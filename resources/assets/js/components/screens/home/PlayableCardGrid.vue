<template>
  <ol class="grid grid-cols-1 md:grid-cols-2">
    <PlayableCard v-for="playable in playables" :key="playable.id" :playable />
  </ol>
</template>

<script lang="ts" setup>
import { toRefs } from 'vue'

import PlayableCard from '@/components/screens/home/PlayableCard.vue'

const props = defineProps<{ playables: Playable[] }>()
const { playables } = toRefs(props)
</script>

<style lang="postcss" scoped>
/* 1-col: divider above every item except the first */
ol > :deep(:nth-child(n + 2)) {
  @apply border-t border-k-fg-5;
}

/* 2-col: only the left-column items (odd) in row 2+ draw the divider */
@media (min-width: 768px) {
  ol > :deep(:nth-child(n + 2)) {
    @apply border-t-0;
  }

  ol > :deep(:nth-child(odd):nth-child(n + 3)) {
    @apply relative;

    &::before {
      @apply content-[''] absolute top-0 left-0 h-px bg-k-fg-5;
      width: 200%;
    }
  }
}
</style>
