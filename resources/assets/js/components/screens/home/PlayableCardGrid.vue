<template>
  <ol class="grid grid-cols-1 md:grid-cols-2 -mx-6">
    <PlayableCard v-for="playable in playables" :key="playable.id" :playable />
  </ol>
</template>

<script lang="ts" setup>
import { toRefs } from 'vue'

import PlayableCard from '@/components/screens/home/PlayableCard.vue'

const props = defineProps<{ playables: Playable[] }>()
const { playables } = toRefs(props)
</script>
