<template>
  <div
    v-if="allowsUpload && mediaPathSetUp"
    :class="{ droppable }"
    class="drop-zone"
    @dragleave="onDropLeave"
    @dragover="onDragOver"
    @drop="onDrop"
  >
    <icon :icon="faUpload" size="6x"/>
    <h3>Drop to upload</h3>
  </div>
</template>

<script lang="ts" setup>
import { ref } from 'vue'
import { faUpload } from '@fortawesome/free-solid-svg-icons'
import { useUpload } from '@/composables'

const { allowsUpload, mediaPathSetUp, handleDropEvent } = useUpload()

const droppable = ref(false)

const onDropLeave = () => (droppable.value = false)

const onDragOver = (event: DragEvent) => {
  if (!event.dataTransfer?.types.includes('Files')) return false
  event.preventDefault()
  event.dataTransfer!.dropEffect = 'copy'
  droppable.value = true
}

const onDrop = async (event: DragEvent) => {
  event.preventDefault()
  droppable.value = false
  await handleDropEvent(event)
}
</script>

<style lang="scss" scoped>
.drop-zone {
  height: 256px;
  max-height: 66vh;
  aspect-ratio: 1/1;
  outline: 3px dashed #ccc;
  position: fixed;
  z-index: 9;
  transform: translate(calc(50vw - 50%), calc(50vh - 50%));
  top: 0;
  left: 0;
  border-radius: 2rem;
  background: rgba(0, 0, 0, .4);
  display: flex;
  align-content: center;
  justify-content: center;
  flex-direction: column;
  align-items: center;
  overflow: hidden;
  transition: 0.2s;

  h3 {
    font-size: 2rem;
    font-weight: var(--font-weight-thin);
    margin-top: 1rem;
  }

  &.droppable {
    height: 384px;
    outline: 3px dashed #fff;
    background: rgba(0, 0, 0, .9);
    box-shadow: 0 0 0 999rem rgba(0, 0, 0, .7);
  }
}
</style>
