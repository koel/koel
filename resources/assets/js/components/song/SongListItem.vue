<template>
  <div
    :class="{ playing, selected: item.selected }"
    class="song-item"
    draggable="true"
    @click="clicked"
    @dragleave="dragLeave"
    @dragstart="dragStart"
    @dblclick.prevent="play"
    @dragenter.prevent="dragEnter"
    @dragover.prevent
    @drop.stop.prevent="drop"
    @contextmenu.stop.prevent="contextMenu"
  >
    <span v-if="columns.includes('track')" class="track-number text-secondary">{{ song.track || '' }}</span>
    <span v-if="columns.includes('title')" class="title">{{ song.title }}</span>
    <span v-if="columns.includes('artist')" class="artist">{{ song.artist.name }}</span>
    <span v-if="columns.includes('album')" class="album">{{ song.album.name }}</span>
    <span v-if="columns.includes('length')" class="time text-secondary">{{ song.fmtLength }}</span>
    <span class="favorite">
      <LikeButton :song="song"/>
    </span>
    <span class="play" role="button" @click.stop="doPlayback">
      <i class="fa fa-pause-circle" v-if="song.playbackState === 'Playing'"></i>
      <i class="fa fa-play-circle" v-else></i>
    </span>
  </div>
</template>

<script lang="ts" setup>
import { ComponentInternalInstance, computed, defineAsyncComponent, getCurrentInstance, toRefs } from 'vue'
import { playbackService } from '@/services'
import { queueStore } from '@/stores'

const LikeButton = defineAsyncComponent(() => import('@/components/song/SongLikeButton.vue'))

const props = defineProps<{ item: SongProxy, columns: SongListColumn[] }>()
const { item, columns } = toRefs(props)

const song = computed(() => item.value.song)
const playing = computed(() => ['Playing', 'Paused'].includes(song.value.playbackState!))

const play = () => {
  queueStore.queueIfNotQueued(song.value)
  playbackService.play(song.value)
}

const doPlayback = () => {
  switch (song.value.playbackState) {
    case 'Playing':
      playbackService.pause()
      break

    case 'Paused':
      playbackService.resume()
      break

    default:
      play()
      break
  }
}

const getParentSongList = (instance: ComponentInternalInstance): ComponentInternalInstance => {
  if (!instance.parent) {
    throw new Error('Cannot find a parent song list anywhere in the tree')
  }

  return instance.parent.proxy?.$options.name === 'SongList' ? instance.parent : getParentSongList(instance.parent)
}

const vm = getCurrentInstance()!
const exposes = getParentSongList(vm).exposed

const clicked = (event: MouseEvent) => exposes?.rowClicked(vm, event)
const dragStart = (event: DragEvent) => exposes?.dragStart(vm, event)
const dragLeave = (event: DragEvent) => exposes?.removeDroppableState(event)
const dragEnter = (event: DragEvent) => exposes?.allowDrop(event)
const drop = (event: DragEvent) => exposes?.handleDrop(vm, event)
const contextMenu = (event: MouseEvent) => exposes?.openContextMenu(vm, event)
</script>

<style lang="scss">
.song-item {
  border-bottom: 1px solid var(--color-bg-secondary);
  max-width: 100% !important; // overriding .item
  height: 35px;
  display: flex;

  &:hover {
    background: rgba(255, 255, 255, .05);
  }

  .play {
    i {
      font-size: 1.5rem;
    }
  }

  .favorite .fa-heart, .favorite:hover .fa-heart-o {
    color: var(--color-maroon);
  }

  &.selected {
    background-color: rgba(255, 255, 255, .08);
  }

  &.playing span {
    color: var(--color-highlight);
  }
}
</style>
