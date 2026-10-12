<template>
  <button
    type="button"
    class="absolute bottom-2 right-2 z-20 w-9 h-9 grid place-items-center rounded-full hover:bg-k-fg-10 opacity-0 group-hover/card:opacity-100 no-hover:opacity-100 transition-[opacity,background-color]"
    v-koel-tooltip
    @click.stop="emit('click', $event)"
  >
    <Icon :icon="faEllipsisVertical" />
    <span class="sr-only">More actions</span>
  </button>
</template>

<script lang="ts" setup>
import { faEllipsisVertical } from '@fortawesome/free-solid-svg-icons'

const emit = defineEmits<{ (e: 'click', event: MouseEvent): void }>()
</script>
