<template>
  <OnClickOutside @trigger="close">
    <div
      v-if="allowsUpload && mediaPathSetUp"
      :class="{ droppable }"
      class="drop-zone h-screen w-screen fixed z-[9999] top-0 left-0
       bg-black/40 flex flex-col-reverse items-center justify-center
      overflow-hidden duration-200"
      @dragleave="onDropLeave"
      @dragover="onDragOver"
      @drop="onDrop"
    >
      <h3 class="text-3xl mt-4 font-extralight">Drop to upload</h3>
      <Icon :icon="faUpload" size="6x" />
    </div>
  </OnClickOutside>
</template>

<script lang="ts" setup>
import { faUpload } from '@fortawesome/free-solid-svg-icons'
import { ref } from 'vue'
import { OnClickOutside } from '@vueuse/components'
import { useUpload } from '@/composables/useUpload'

const emit = defineEmits<{ (e: 'close'): void }>()

const { allowsUpload, mediaPathSetUp, handleDropEvent } = useUpload()

const droppable = ref(false)

const onDropLeave = () => (droppable.value = false)

const onDragOver = (event: DragEvent) => {
  if (!event.dataTransfer?.types.includes('Files')) {
    return false
  }
  event.preventDefault()
  droppable.value = true
}

const onDrop = async (event: DragEvent) => {
  event.preventDefault()
  droppable.value = false
  await handleDropEvent(event)
}

const close = () => emit('close')
</script>

<style lang="postcss" scoped>
.drop-zone {
  &.droppable {
    @apply bg-black/80;
  }
}
</style>
