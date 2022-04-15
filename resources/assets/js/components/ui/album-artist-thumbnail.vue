<template>
  <span
    :style="{ backgroundImage: `url(${backgroundImageUrl})` }"
    class="cover"
    :class="{ droppable }"
  >
    <a
      @click.prevent="playOrQueue"
      class="control control-play font-size-0"
      href
      role="button"
      @dragenter.prevent="onDragEnter"
      @dragleave.prevent="onDragLeave"
      @drop.stop.prevent="onDrop"
      @dragover.prevent
    >
      {{ buttonLabel }}
    </a>
  </span>
</template>

<script lang="ts">
import Vue, { PropOptions } from 'vue'
import { orderBy } from 'lodash'
import { queueStore, albumStore, artistStore, userStore } from '@/stores'
import { playback } from '@/services'
import { getDefaultCover, fileReader } from '@/utils'

const VALID_IMAGE_TYPES = ['image/jpeg', 'image/gif', 'image/png']

export default Vue.extend({
  props: {
    entity: {
      type: Object,
      required: true
    } as PropOptions<Album | Artist>
  },

  data: () => ({
    droppable: false,
    userState: userStore.state
  }),

  computed: {
    forAlbum (): boolean {
      return 'artist' in this.entity
    },

    sortFields (): string[] {
      return this.forAlbum ? ['disc', 'track'] : ['album_id', 'disc', 'track']
    },

    backgroundImageUrl (): string {
      if (this.forAlbum) {
        const entity = this.entity as Album
        return entity.cover ? entity.cover : getDefaultCover()
      } else {
        const entity = this.entity as Artist
        return entity.image ? entity.image : getDefaultCover()
      }
    },

    buttonLabel (): string {
      return this.forAlbum
        ? `Play all songs in the album ${this.entity.name}`
        : `Play all songs by the artist ${this.entity.name}`
    },

    playbackFunc (): Function {
      return this.forAlbum ? playback.playAllInAlbum : playback.playAllByArtist
    },

    allowsUpload (): boolean {
      return this.userState.current.is_admin
    }
  },

  methods: {
    playOrQueue (e: KeyboardEvent) {
      if (e.metaKey || e.ctrlKey) {
        queueStore.queue(orderBy(this.entity.songs, this.sortFields))
      } else {
        this.playbackFunc.call(playback, this.entity, false)
      }
    },

    onDragEnter (): void {
      this.droppable = this.allowsUpload
    },

    onDragLeave (): void {
      this.droppable = false
    },

    async onDrop (e: DragEvent): Promise<void> {
      this.droppable = false

      if (!this.allowsUpload) {
        return
      }

      if (!this.validImageDropEvent(e)) {
        return
      }

      try {
        const fileData = await fileReader.readAsDataUrl(e.dataTransfer!.files[0])

        if (this.forAlbum) {
          // Replace the image right away to create a swift effect
          (this.entity as Album).cover = fileData
          albumStore.uploadCover(this.entity as Album, fileData)
        } else {
          (this.entity as Artist).image = fileData
          artistStore.uploadImage(this.entity as Artist, fileData)
        }
      } catch (exception) {
        /* eslint no-console: 0 */
        console.error(exception)
      }
    },

    validImageDropEvent: (e: DragEvent): boolean => {
      if (!e.dataTransfer || !e.dataTransfer.items) {
        return false
      }

      if (e.dataTransfer.items.length !== 1) {
        return false
      }

      if (e.dataTransfer.items[0].kind !== 'file') {
        return false
      }

      if (!VALID_IMAGE_TYPES.includes(e.dataTransfer.items[0].getAsFile()!.type)) {
        return false
      }

      return true
    }
  }
})
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
