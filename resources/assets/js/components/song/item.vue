<template>
  <div
    class="song-item"
    draggable="true"
    @click="clicked"
    @dblclick.prevent="playRightAwayyyyyyy"
    @dragstart="dragStart"
    @dragleave="dragLeave"
    @dragenter.prevent="dragEnter"
    @dragover.prevent
    @drop.stop.prevent="drop"
    @contextmenu.stop.prevent="contextMenu"
    :class="{ playing, selected: item.selected }"
  >
    <span class="track-number text-secondary" v-if="columns.includes('track')">{{ song.track || '' }}</span>
    <span class="title" v-if="columns.includes('title')">{{ song.title }}</span>
    <span class="artist" v-if="columns.includes('artist')">{{ song.artist.name }}</span>
    <span class="album" v-if="columns.includes('album')">{{ song.album.name }}</span>
    <span class="time text-secondary" v-if="columns.includes('length')">{{ song.fmtLength }}</span>
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
import { playback } from '@/services'
import { queueStore } from '@/stores'

const LikeButton = defineAsyncComponent(() => import('@/components/song/like-button.vue'))

const props = defineProps<{ item: SongProxy, columns: SongListColumn[] }>()
const { item, columns } = toRefs(props)

const song = computed(() => item.value.song)
const playing = computed(() => ['Playing', 'Paused'].includes(song.value.playbackState!))

const playRightAwayyyyyyy = () => {
  queueStore.contains(song.value) || queueStore.queueAfterCurrent(song.value)
  playback.play(song.value)
}

const doPlayback = () => {
  switch (song.value.playbackState) {
    case 'Playing':
      playback.pause()
      break

    case 'Paused':
      playback.resume()
      break

    default:
      playRightAwayyyyyyy()
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
