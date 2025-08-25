<template>
  <AlbumContextMenu />
  <ArtistContextMenu />
  <CreateNewPlaylistContextMenu />
  <GenreContextMenu />
  <MediaBrowserContextMenu />
  <PlayableContextMenu />
  <PlaylistContextMenu />
  <PlaylistFolderContextMenu />
  <PodcastContextMenu />
  <RadioStationContextMenu />
</template>

<script setup lang="ts">
import { defineAsyncComponent } from '@/utils/helpers'

const AlbumContextMenu = defineAsyncComponent(() => import('@/components/album/AlbumContextMenu.vue'))
const ArtistContextMenu = defineAsyncComponent(() => import('@/components/artist/ArtistContextMenu.vue'))
const CreateNewPlaylistContextMenu = defineAsyncComponent(() => import('@/components/playlist/CreatePlaylistContextMenu.vue'))
const GenreContextMenu = defineAsyncComponent(() => import('@/components/genre/GenreContextMenu.vue'))
const MediaBrowserContextMenu = defineAsyncComponent(() => import('@/components/media-browser/MediaBrowserContextMenu.vue'))
const PlayableContextMenu = defineAsyncComponent(() => import('@/components/playable/PlayableContextMenu.vue'))
const PlaylistContextMenu = defineAsyncComponent(() => import('@/components/playlist/PlaylistContextMenu.vue'))
const PlaylistFolderContextMenu = defineAsyncComponent(() => import('@/components/playlist/PlaylistFolderContextMenu.vue'))
const PodcastContextMenu = defineAsyncComponent(() => import('@/components/podcast/PodcastContextMenu.vue'))
const RadioStationContextMenu = defineAsyncComponent(() => import('@/components/radio/RadioStationContextMenu.vue'))
</script>
