<template>
  <section id="favoritesWrapper">
    <ScreenHeader>
      Songs You Love
      <ControlsToggler :showing-controls="showingControls" @toggleControls="toggleControls"/>

      <template v-slot:meta>
        <span v-if="songs.length">
          {{ pluralize(songs.length, 'song') }}
          •
          {{ duration }}
          <template v-if="allowDownload">
            •
            <a class="download" href role="button" title="Download all songs in playlist" @click.prevent="download">
              Download All
            </a>
          </template>
        </span>
      </template>

      <template v-slot:controls>
        <SongListControls
          v-if="songs.length && (!isPhone || showingControls)"
          :config="songListControlConfig"
          :selectedSongs="selectedSongs"
          :songs="songs"
          @playAll="playAll"
          @playSelected="playSelected"
        />
      </template>
    </ScreenHeader>

    <SongList
      v-if="songs.length"
      ref="songList"
      :items="songs"
      type="favorites"
      @press:delete="removeSelected"
      @press:enter="onPressEnter"
    />

    <ScreenEmptyState v-else>
      <template v-slot:icon>
        <i class="fa fa-frown-o"></i>
      </template>
      No favorites yet.
      <span class="secondary d-block">
        Click the&nbsp;
        <i class="fa fa-heart-o"></i>&nbsp;
        icon to mark a song as favorite.
      </span>
    </ScreenEmptyState>
  </section>
</template>

<script lang="ts" setup>
import { pluralize } from '@/utils'
import { favoriteStore, sharedStore } from '@/stores'
import { download as downloadService } from '@/services'
import { useSongList } from '@/composables'
import { defineAsyncComponent, toRef } from 'vue'

const ScreenHeader = defineAsyncComponent(() => import('@/components/ui/ScreenHeader.vue'))
const ScreenEmptyState = defineAsyncComponent(() => import('@/components/ui/ScreenEmptyState.vue'))

const {
  SongList,
  SongListControls,
  ControlsToggler,
  songs,
  songList,
  duration,
  selectedSongs,
  showingControls,
  songListControlConfig,
  isPhone,
  onPressEnter,
  playAll,
  playSelected,
  toggleControls
} = useSongList(toRef(favoriteStore.state, 'songs'))

const allowDownload = toRef(sharedStore.state, 'allowDownload')

const download = () => downloadService.fromFavorites()
const removeSelected = () => selectedSongs.value.length && favoriteStore.unlike(selectedSongs.value)
</script>
