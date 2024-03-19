<template>
  <div
    :class="{ droppable }"
    :style="{ backgroundImage: `url(${playlist.cover || defaultCover})` }"
    class="cover"
    data-testid="playlist-thumbnail"
    @dragenter.prevent="onDragEnter"
    @dragleave.prevent="onDragLeave"
    @drop.prevent="onDrop"
    @dragover.prevent
  >
    <div class="pointer-events-none">
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, toRef, toRefs } from 'vue'
import { defaultCover, logger } from '@/utils'
import { playlistStore, userStore } from '@/stores'
import { useAuthorization, useFileReader, useKoelPlus, useMessageToaster } from '@/composables'
import { acceptedImageTypes } from '@/config'

const props = defineProps<{ playlist: Playlist }>()
const { playlist } = toRefs(props)

const droppable = ref(false)
const user = toRef(userStore.state, 'current')

const { isAdmin } = useAuthorization()
const { isPlus } = useKoelPlus()
const { toastError } = useMessageToaster()

const allowsUpload = computed(() => isAdmin.value || isPlus.value)
const onDragEnter = () => (droppable.value = allowsUpload.value)
const onDragLeave = () => (droppable.value = false)

const validImageDropEvent = (event: DragEvent) => {
  if (!event.dataTransfer || !event.dataTransfer.items) {
    return false
  }

  if (event.dataTransfer.items.length !== 1) {
    return false
  }

  if (event.dataTransfer.items[0].kind !== 'file') {
    return false
  }

  return acceptedImageTypes.includes(event.dataTransfer.items[0].getAsFile()!.type)
}

const onDrop = async (event: DragEvent) => {
  droppable.value = false

  if (!allowsUpload.value) {
    return
  }

  if (!validImageDropEvent(event)) {
    return
  }

  const backupImage = playlist.value.cover

  try {
    useFileReader().readAsDataUrl(event.dataTransfer!.files[0], async url => {
      // Replace the image right away to create an "instant" effect
      playlist.value.cover = url
      await playlistStore.uploadCover(playlist.value, url)
    })
  } catch (e) {
    const message = e?.response?.data?.message ?? 'Unknown error.'
    toastError(`Failed to upload: ${message}`)

    // restore the backup image
    playlist.value.cover = backupImage

    logger.error(e)
  }
}
</script>

<style scoped lang="scss">
.cover {
  position: relative;
  width: 100%;
  aspect-ratio: 1/1;
  display: block;
  background-repeat: no-repeat;
  background-size: cover;
  background-position: center center;
  border-radius: 5px;
  overflow: hidden;

  &.droppable {
    border: 2px dotted rgba(255, 255, 255, 1);
    filter: brightness(0.4);
  }

  .thumbnail-stack {
    pointer-events: none;
  }
}
</style>
