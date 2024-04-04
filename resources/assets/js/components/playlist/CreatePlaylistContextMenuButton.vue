<template>
  <button
    class="relative before:absolute before:w-[28px] before:aspect-square before:top-[-6px] before:left-[-6px] before:cursor-pointer"
    title="Create a new playlist or folder"
    type="button"
    @click.stop.prevent="requestContextMenu"
  >
    <Icon :icon="faCirclePlus" />
  </button>
</template>

<script setup lang="ts">
import { faCirclePlus } from '@fortawesome/free-solid-svg-icons'
import { eventBus } from '@/utils'

const requestContextMenu = (e: MouseEvent) => {
  const { bottom, right } = (e.currentTarget as HTMLButtonElement).getBoundingClientRect()

  eventBus.emit('CREATE_NEW_PLAYLIST_CONTEXT_MENU_REQUESTED', {
    top: bottom,
    left: right
  })
}
</script>
