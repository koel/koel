<template>
  <template v-if="playables.length === 1 && isSong(playables[0])">
    <Separator />
    <li
      tabindex="-1"
      class="px-4 py-2 focus:outline-hidden"
      @mouseover="($event.currentTarget as HTMLLIElement).focus()"
    >
      <StarRating :rateable="playables[0] as Song" @rate="closeContextMenu" />
    </li>
    <Separator />
  </template>
</template>

<script lang="ts" setup>
import { isSong } from '@/utils/typeGuards'
import { useContextMenu } from '@/composables/useContextMenu'

import StarRating from '@/components/ui/StarRating.vue'

defineProps<{ playables: Playable[] }>()

const { Separator, closeContextMenu } = useContextMenu()
</script>
