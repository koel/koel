<template>
  <button
    :class="{ droppable }"
    :style="{ backgroundImage: `url(${defaultCover})` }"
    class="thumbnail relative w-full aspect-square bg-no-repeat bg-cover bg-center overflow-hidden rounded-md active:scale-95"
    data-testid="album-artist-thumbnail"
    @click.prevent="playOrQueue"
    @dragenter.prevent="onDragEnter"
    @dragleave.prevent="onDragLeave"
    @drop.prevent="onDrop"
    @dragover.prevent
  >
    <img
      v-koel-hide-broken-icon
      alt="Thumbnail"
      :src="image"
      class="w-full aspect-square object-cover"
      loading="lazy"
    >
    <span class="hidden">{{ buttonLabel }}</span>
    <span class="absolute top-0 left-0 w-full h-full group-hover:bg-black/40 z-10" />
    <span
      class="play-icon absolute flex opacity-0 items-center justify-center w-[32px] aspect-square rounded-full top-1/2
      left-1/2 -translate-x-1/2 -translate-y-1/2 bg-k-highlight group-hover:opacity-100 duration-500 transition z-20"
    >
      <Icon :icon="faPlay" class="ml-1 text-white" size="lg" />
    </span>
  </button>
</template>

<script lang="ts" setup>
import { orderBy } from 'lodash'
import { computed, ref, toRefs } from 'vue'
import { albumStore, artistStore, queueStore, songStore } from '@/stores'
import { playbackService } from '@/services'
import { defaultCover } from '@/utils'
import { useErrorHandler, useFileReader, useMessageToaster, usePolicies, useRouter } from '@/composables'
import { acceptedImageTypes } from '@/config'
import { faPlay } from '@fortawesome/free-solid-svg-icons'

const { toastSuccess } = useMessageToaster()
const { go } = useRouter()
const { currentUserCan } = usePolicies()

const props = defineProps<{ entity: Album | Artist }>()
const { entity } = toRefs(props)

const droppable = ref(false)

const forAlbum = computed(() => entity.value.type === 'albums')
const sortFields = computed(() => forAlbum.value ? ['disc', 'track'] : ['album_id', 'disc', 'track'])

const image = computed(() => {
  return forAlbum.value
    ? (entity.value as Album).cover || defaultCover
    : (entity.value as Artist).image || defaultCover
})

const buttonLabel = computed(() => forAlbum.value
  ? `Play all songs in the album ${entity.value.name}`
  : `Play all songs by ${entity.value.name}`
)

const allowsUpload = currentUserCan.changeAlbumOrArtistThumbnails()

const playOrQueue = async (event: MouseEvent) => {
  const songs = forAlbum.value
    ? await songStore.fetchForAlbum(entity.value as Album)
    : await songStore.fetchForArtist(entity.value as Artist)

  if (event.altKey) {
    queueStore.queue(orderBy(songs, sortFields.value))
    toastSuccess('Songs added to queue.')
    return
  }

  playbackService.queueAndPlay(songs)
  go('queue')
}

const onDragEnter = () => (droppable.value = allowsUpload)

const onDragLeave = (e: DragEvent) => {
  if ((e.currentTarget as Node)?.contains?.(e.relatedTarget as Node)) {
    return
  }

  droppable.value = false
}

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

  if (!allowsUpload) {
    return
  }

  if (!validImageDropEvent(event)) {
    return
  }

  const backupImage = forAlbum.value ? (entity.value as Album).cover : (entity.value as Artist).image

  try {
    useFileReader().readAsDataUrl(event.dataTransfer!.files[0], async url => {
      if (forAlbum.value) {
        // Replace the image right away to create an "instant" effect
        (entity.value as Album).cover = url
        await albumStore.uploadCover(entity.value as Album, url)
      } else {
        (entity.value as Artist).image = url as string
        await artistStore.uploadImage(entity.value as Artist, url)
      }
    })
  } catch (error: unknown) {
    // restore the backup image
    if (forAlbum.value) {
      (entity.value as Album).cover = backupImage!
    } else {
      (entity.value as Artist).image = backupImage!
    }

    useErrorHandler().handleHttpError(error)
  }
}
</script>

<style lang="postcss" scoped>
.droppable {
  @apply border-2 border-dotted border-white brightness-50;

  * {
    pointer-events: none;
  }
}

.compact .icon {
  @apply text-[.3rem];
  /* to control the size of the icon */
}
</style>
