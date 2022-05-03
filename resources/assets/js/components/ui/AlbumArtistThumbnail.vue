<template>
  <span
    :class="{ droppable }"
    :style="{ backgroundImage: `url(${image})` }"
    class="cover"
    data-testid="album-thumbnail"
  >
    <a
      class="control control-play font-size-0"
      href
      role="button"
      @click.prevent="playOrQueue"
      @dragenter.prevent="onDragEnter"
      @dragleave.prevent="onDragLeave"
      @drop.stop.prevent="onDrop"
      @dragover.prevent
    >
      {{ buttonLabel }}
    </a>
  </span>
</template>

<script lang="ts" setup>
import { orderBy } from 'lodash'
import { computed, ref, toRef, toRefs } from 'vue'
import { albumStore, artistStore, queueStore, userStore } from '@/stores'
import { playbackService } from '@/services'
import { defaultCover, fileReader } from '@/utils'
import { useAuthorization } from '@/composables'

const VALID_IMAGE_TYPES = ['image/jpeg', 'image/gif', 'image/png']

const props = defineProps<{ entity: Album | Artist }>()
const { entity } = toRefs(props)

const droppable = ref(false)
const user = toRef(userStore.state, 'current')

const forAlbum = computed(() => 'artist' in entity.value)
const sortFields = computed(() => forAlbum.value ? ['disc', 'track'] : ['album_id', 'disc', 'track'])

const image = computed(() => {
  if (forAlbum.value) {
    return (entity.value as Album).cover ? (entity.value as Album).cover : defaultCover
  }

  return getArtistImage(entity.value as Artist)
})

const getArtistImage = (artist: Artist) => {
  // If the artist has no image, try getting the cover from one of their albums
  if (!artist.image) {
    artist.albums.every(album => {
      if (album.cover !== defaultCover) {
        artist.image = album.cover
        return false
      }
    })
  }

  artist.image = artist.image ?? defaultCover

  return artist.image
}

const buttonLabel = computed(() => forAlbum.value
  ? `Play all songs in the album ${entity.value.name}`
  : `Play all songs by the artist ${entity.value.name}`
)

const { isAdmin: allowsUpload } = useAuthorization()

const playOrQueue = (event: KeyboardEvent) => {
  if (event.metaKey || event.ctrlKey) {
    queueStore.queue(orderBy(entity.value.songs, sortFields.value))
    return
  }

  if (forAlbum.value) {
    playbackService.playAllInAlbum(entity.value as Album, false)
  } else {
    playbackService.playAllByArtist(entity.value as Artist, false)
  }
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
      // Replace the image right away to create a swift effect
      (entity.value as Album).cover = fileData
      await albumStore.uploadCover(entity.value as Album, fileData)
    } else {
      (entity.value as Artist).image = fileData
      await artistStore.uploadImage(entity.value as Artist, fileData)
    }
  } catch (exception) {
    console.error(exception)
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

    &::after {
      content: "";
      width: 60%;
      max-width: 128px;
      height: 60%;
      max-height: 128px;
      background-image: url(data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTEzcHgiIGhlaWdodD0iMTMxcHgiIHZpZXdCb3g9IjAgMCAxMTMgMTMxIiB2ZXJzaW9uPSIxLjEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiPgogICAgPGcgaWQ9InRyaWFuZ2xlIiBzdHJva2U9Im5vbmUiIHN0cm9rZS13aWR0aD0iMSIgZmlsbD0ibm9uZSIgZmlsbC1ydWxlPSJldmVub2RkIj4KICAgICAgICA8cG9seWdvbiBpZD0iUG9seWdvbiIgZmlsbD0iI0ZGRkZGRiIgcG9pbnRzPSIxMTMuMDIzNzI5IDY1LjI1NDI4MDMgLTEuNTg1Njc4MzFlLTE0IDEzMC41MDg1NjEgLTUuNjg0MzQxODllLTE0IDAiPjwvcG9seWdvbj4KICAgIDwvZz4KPC9zdmc+);
      background-size: 45%;
      background-position: 58% 50%;
      background-repeat: no-repeat;
      border-radius: 50%;
      background-color: var(--color-bg-primary);
      opacity: 0;
      z-index: 2;

      @media (hover: none) {
        opacity: .5;
      }
    }

    &:hover, &:focus {
      &::before, &::after {
        transition: .3s opacity;
        opacity: 1;
      }
    }

    &:active {
      &::before {
        background: rgba(0, 0, 0, .5);
      }

      &::after {
        transform: scale(.9);
      }
    }
  }

  .drop-zone {
    font-size: 4rem;
    position: absolute;
    width: 100%;
    height: 100%;
    display: flex;
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
</style>
