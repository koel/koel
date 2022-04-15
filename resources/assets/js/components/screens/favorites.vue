<template>
  <section id="favoritesWrapper">
    <ScreenHeader>
      Songs You Love
      <ControlsToggler :showing-controls="showingControls" @toggleControls="toggleControls"/>

      <template v-slot:meta>
        <span v-if="meta.songCount">
          {{ pluralize(meta.songCount, 'song') }}
          •
          {{ meta.totalLength }}
          <template v-if="sharedState.allowDownload && state.songs.length">
            •
            <a href @click.prevent="download" class="download" title="Download all songs in playlist" role="button">
              Download All
            </a>
          </template>
        </span>
      </template>

      <template v-slot:controls>
        <SongListControls
          v-if="state.songs.length && (!isPhone || showingControls)"
          @playAll="playAll"
          @playSelected="playSelected"
          :songs="state.songs"
          :config="songListControlConfig"
          :selectedSongs="selectedSongs"
        />
      </template>
    </ScreenHeader>

    <SongList v-if="state.songs.length" :items="state.songs" type="favorites" ref="songList"/>

    <ScreenPlaceholder v-else>
      <template v-slot:icon>
        <i class="fa fa-frown-o"></i>
      </template>
      No favorites yet.
      <span class="secondary d-block">
        Click the
        <i class="fa fa-heart-o"></i>
        icon to mark a song as favorite.
      </span>
    </ScreenPlaceholder>
  </section>
</template>

<script lang="ts" setup>
import { pluralize } from '@/utils'
import { favoriteStore, sharedStore } from '@/stores'
import { download as downloadService } from '@/services'
import { useSongList } from '@/composables'
import { defineAsyncComponent, reactive } from 'vue'

const ScreenHeader = defineAsyncComponent(() => import('@/components/ui/screen-header.vue'))
const ScreenPlaceholder = defineAsyncComponent(() => import('@/components/ui/screen-placeholder.vue'))

const {
  SongList,
  SongListControls,
  ControlsToggler,
  songList,
  meta,
  selectedSongs,
  showingControls,
  songListControlConfig,
  isPhone,
  playAll,
  playSelected,
  toggleControls
} = useSongList()

const state = reactive(favoriteStore.state)
const sharedState = reactive(sharedStore.state)

const download = () => downloadService.fromFavorites()
</script>
