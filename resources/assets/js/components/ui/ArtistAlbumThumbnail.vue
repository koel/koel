<template>
  <div
    :class="{ droppable }"
    :style="{ backgroundImage: `url(${defaultCover})` }"
    class="cover relative w-full aspect-square bg-no-repeat bg-cover bg-center overflow-hidden rounded-md
    after:block after:pt-[100%]"
    data-testid="album-artist-thumbnail"
  >
    <img
      v-koel-hide-broken-icon
      :alt="entity.name"
      :src="image"
      class="w-full h-full object-cover absolute left-0 top-0 pointer-events-none
      before:absolute before:w-full before:h-full before:opacity-0 before:z-[1] before-top-0"
      loading="lazy"
    >
    <a
      class="control control-play h-full w-full absolute flex justify-center items-center"
      role="button"
      @click.prevent="playOrQueue"
      @dragenter.prevent="onDragEnter"
      @dragleave.prevent="onDragLeave"
      @drop.prevent="onDrop"
      @dragover.prevent
    >
      <span class="hidden">{{ buttonLabel }}</span>
      <span
        class="icon opacity-0 w-1/2 h-1/2 flex justify-center items-center pointer-events-none pl-[4%] rounded-full
        after:w-full after:h-full"
      />
    </a>
  </div>
</template>

<script lang="ts" setup>
import { orderBy } from 'lodash'
import { computed, ref, toRefs } from 'vue'
import { albumStore, artistStore, queueStore, songStore } from '@/stores'
import { playbackService } from '@/services'
import { defaultCover } from '@/utils'
import { useErrorHandler, useFileReader, useMessageToaster, usePolicies, useRouter } from '@/composables'
import { acceptedImageTypes } from '@/config'

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
.icon {
  @apply bg-k-bg-primary text-k-highlight no-hover:opacity-100;

  &::after {
    @apply bg-k-highlight;

    mask-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path fill="currentColor" d="M361 215C375.3 223.8 384 239.3 384 256C384 272.7 375.3 288.2 361 296.1L73.03 472.1C58.21 482 39.66 482.4 24.52 473.9C9.377 465.4 0 449.4 0 432V80C0 62.64 9.377 46.63 24.52 38.13C39.66 29.64 58.21 29.99 73.03 39.04L361 215z"/></svg>');
    mask-repeat: no-repeat;
    mask-position: center;
    mask-size: 40%;
  }
}

article {
  .control {
    &:hover, &:focus {
      &::before, .icon {
        @apply transition-opacity duration-300 opacity-100;
      }
    }

    &:active {
      &::before {
        @apply bg-black/50;
      }

      .icon {
        @apply scale-90;
      }
    }
  }

  &.droppable {
    @apply border-2 border-dotted border-white brightness-50;

    .control {
      @apply opacity-0;
    }
  }
}

.compact .icon {
  @apply text-[.3rem];
  /* to control the size of the icon */
}
</style>
