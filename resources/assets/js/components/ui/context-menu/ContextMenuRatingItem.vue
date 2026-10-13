<template>
  <Separator />
  <li tabindex="-1" class="px-4 py-2 focus:outline-hidden" @mouseover="($event.currentTarget as HTMLLIElement).focus()">
    <StarRating :rateable @rate="closeContextMenu" />
  </li>
  <Separator />
</template>

<script lang="ts" setup>
import { useContextMenu } from '@/composables/useContextMenu'

import StarRating from '@/components/ui/StarRating.vue'

defineProps<{ rateable: Rateable }>()

const { Separator, closeContextMenu } = useContextMenu()
</script>
