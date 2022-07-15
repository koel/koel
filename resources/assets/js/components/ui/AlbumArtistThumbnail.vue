<template>
  <span
    :class="{ droppable }"
    :style="{ backgroundImage: `url(${image}), url(${defaultCover})` }"
    class="cover"
    data-testid="album-artist-thumbnail"
  >
    <a
      class="control control-play"
      href
      role="button"
      @click.prevent="playOrQueue"
      @dragenter.prevent="onDragEnter"
      @dragleave.prevent="onDragLeave"
      @drop.stop.prevent="onDrop"
      @dragover.prevent
    >
      <span class="hidden">{{ buttonLabel }}</span>
      <span class="icon-wrapper">
        <icon :icon="faPlay" size="5x"/>
      </span>
    </a>
  </span>
</template>

<script lang="ts" setup>
import { faPlay } from '@fortawesome/free-solid-svg-icons'
import { orderBy } from 'lodash'
import { computed, ref, toRef, toRefs } from 'vue'
import { albumStore, artistStore, queueStore, songStore, userStore } from '@/stores'
import { playbackService } from '@/services'
import { alerts, defaultCover, fileReader } from '@/utils'
import { useAuthorization } from '@/composables'

const VALID_IMAGE_TYPES = ['image/jpeg', 'image/gif', 'image/png']

const props = defineProps<{ entity: Album | Artist }>()
const { entity } = toRefs(props)

const droppable = ref(false)
const user = toRef(userStore.state, 'current')

const forAlbum = computed(() => entity.value.type === 'albums')
const sortFields = computed(() => forAlbum.value ? ['disc', 'track'] : ['album_id', 'disc', 'track'])

const image = computed(() => {
  if (forAlbum.value) {
    return (entity.value as Album).cover ? (entity.value as Album).cover : defaultCover
  }

  return getArtistImage(entity.value as Artist)
})

const getArtistImage = (artist: Artist) => {
  artist.image = artist.image ?? defaultCover

  return artist.image
}

const buttonLabel = computed(() => forAlbum.value
  ? `Play all songs in the album ${entity.value.name}`
  : `Play all songs by ${entity.value.name}`
)

const { isAdmin: allowsUpload } = useAuthorization()

const playOrQueue = async (event: KeyboardEvent) => {
  const songs = forAlbum.value
    ? await songStore.fetchForAlbum(entity.value as Album)
    : await songStore.fetchForArtist(entity.value as Artist)

  if (event.altKey) {
    queueStore.queue(orderBy(songs, sortFields.value))
    alerts.success('Songs added to queue.')
    return
  }

  await playbackService.queueAndPlay(songs)
}

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

  return VALID_IMAGE_TYPES.includes(event.dataTransfer.items[0].getAsFile()!.type)
}

const onDrop = async (event: DragEvent) => {
  droppable.value = false

  if (!allowsUpload.value) {
    return
  }

  if (!validImageDropEvent(event)) {
    return
  }

  try {
    const fileData = await fileReader.readAsDataUrl(event.dataTransfer!.files[0])

    if (forAlbum.value) {
      // Replace the image right away to create an "instant" effect
      (entity.value as Album).cover = fileData
      await albumStore.uploadCover(entity.value as Album, fileData)
    } else {
      (entity.value as Artist).image = fileData
      await artistStore.uploadImage(entity.value as Artist, fileData)
    }
  } catch (e) {
    console.error(e)
  }
}
</script>

<style lang="scss" scoped>
.cover {
  position: relative;
  width: 100%;
  display: block;
  background-repeat: no-repeat;
  background-size: cover;
  background-position: center center;
  border-radius: 5px;
  overflow: hidden;

  &::after {
    content: "";
    display: block;
    padding-top: 100%;
  }

  .control {
    height: 100%;
    width: 100%;
    position: absolute;
    display: flex;
    justify-content: center;
    align-items: center;

    &::before {
      position: absolute;
      content: "";
      width: 100%;
      height: 100%;
      top: 0;
      background: rgba(0, 0, 0, .3);
      opacity: 0;
      z-index: 1;
    }

    .icon-wrapper {
      background-color: var(--color-bg-primary);
      color: var(--color-highlight);
      opacity: 0;
      border-radius: 50%;
      width: 50%;
      height: 50%;
      display: flex;
      justify-content: center;
      align-items: center;
      padding-left: 4%;
      z-index: 99;

      @media (hover: none) {
        opacity: .5;
      }
    }

    &:hover, &:focus {
      &::before, .icon-wrapper {
        transition: .3s opacity;
        opacity: 1;
      }
    }

    &:active {
      &::before {
        background: rgba(0, 0, 0, .5);
      }

      .icon-wrapper {
        transform: scale(.9);
      }
    }
  }

  .drop-zone {
    font-size: 4rem;
    position: absolute;
    width: 100%;
    height: 100%;
    place-content: center;
    place-items: center;
    background: rgba(0, 0, 0, .7);
    display: none;
  }

  &.droppable {
    border: 2px dotted rgba(255, 255, 255, 1);
    filter: brightness(0.4);

    .control {
      opacity: 0;
    }
  }
}

.compact .icon-wrapper {
  font-size: .3rem; // to control the size of the icon
}
</style>
