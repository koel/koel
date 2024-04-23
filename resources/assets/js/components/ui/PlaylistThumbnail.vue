<template>
  <article
    :class="{ droppable }"
    class="cover relative w-full aspect-square block rounded-md overflow-hidden bg-no-repeat bg-cover bg-center"
    data-testid="playlist-thumbnail"
    @dragenter.prevent="onDragEnter"
    @dragleave.prevent="onDragLeave"
    @drop.prevent="onDrop"
    @dragover.prevent
  >
    <div class="pointer-events-none">
      <slot />
    </div>
  </article>
</template>

<script setup lang="ts">
import { computed, ref, toRefs } from 'vue'
import { defaultCover } from '@/utils'
import { playlistStore } from '@/stores'
import { useAuthorization, useErrorHandler, useFileReader, useKoelPlus } from '@/composables'
import { acceptedImageTypes } from '@/config'

const props = defineProps<{ playlist: Playlist }>()
const { playlist } = toRefs(props)

const droppable = ref(false)

const { isAdmin } = useAuthorization()
const { isPlus } = useKoelPlus()

const backgroundImage = computed(() => `url(${playlist.value.cover || defaultCover })`)

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
  } catch (error: unknown) {
    // restore the backup image
    playlist.value.cover = backupImage
    useErrorHandler().handleHttpError(error)
  }
}
</script>

<style scoped lang="postcss">
article {
  background-image: v-bind(backgroundImage);

  &.droppable {
    @apply border-2 border-dotted border-white brightness-50;
  }

  .thumbnail-stack {
    @apply pointer-events-none;
  }
}
</style>
