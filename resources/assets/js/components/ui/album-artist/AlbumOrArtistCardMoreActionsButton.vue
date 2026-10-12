<template>
  <button
    type="button"
    class="self-center ml-auto -my-2 -mr-2 w-9 h-9 grid place-items-center rounded-full hover:bg-k-fg-10 opacity-0 group-hover/card:opacity-100 group-focus-within/card:opacity-100 no-hover:opacity-100 transition-[opacity,background-color]"
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
