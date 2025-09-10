<template>
  <OnClickOutside @trigger="close">
    <div
      v-if="allowsUpload && mediaPathSetUp"
      class="drop-zone w-screen h-screen fixed z-50 top-0 left-0 rounded-3xl bg-black/40
      flex items-center justify-center overflow-hidden duration-200"
      @dragleave="onDropLeave"
      @dragover="onDragOver"
      @drop="onDrop"
    >
      <div class="pointer-events-none flex flex-col items-center justify-center gap-4">
        <Icon :icon="faUpload" size="6x" />
        <h3 class="text-3xl font-extralight">Drop to upload</h3>
      </div>
    </div>
  </OnClickOutside>
</template>

<script lang="ts" setup>
import { faUpload } from '@fortawesome/free-solid-svg-icons'
import { OnClickOutside } from '@vueuse/components'
import { useUpload } from '@/composables/useUpload'

const emit = defineEmits<{ (e: 'close'): void }>()

const { allowsUpload, mediaPathSetUp, handleDropEvent } = useUpload()

const onDragOver = (event: DragEvent) => {
  if (!event.dataTransfer?.types.includes('Files')) {
    return false
  }

  event.preventDefault()
}

const onDrop = async (event: DragEvent) => {
  event.preventDefault()
  await handleDropEvent(event)
}

const close = () => emit('close')
</script>
