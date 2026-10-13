<template>
  <ContextMenuRatingItem v-if="playables.length === 1 && isSong(playables[0])" :rateable="playables[0] as Song" />
</template>

<script lang="ts" setup>
import { isSong } from '@/utils/typeGuards'

import ContextMenuRatingItem from '@/components/ui/context-menu/ContextMenuRatingItem.vue'

defineProps<{ playables: Playable[] }>()
</script>
