<template>
  <section id="favoritesWrapper">
    <ScreenHeader>
      Songs You Love
      <ControlsToggle :showing-controls="showingControls" @toggleControls="toggleControls"/>

      <template v-slot:meta v-if="songs.length">
        <span>{{ pluralize(songs.length, 'song') }}</span>
        <span>{{ duration }}</span>

        <a
          v-if="allowDownload"
          class="download"
          href
          role="button"
          title="Download all songs in playlist"
          @click.prevent="download"
        >
          Download All
        </a>
      </template>

      <template v-slot:controls>
        <SongListControls
          v-if="songs.length && (!isPhone || showingControls)"
          @playAll="playAll"
          @playSelected="playSelected"
        />
      </template>
    </ScreenHeader>

    <SongList
      v-show="songs.length"
      ref="songList"
      @press:delete="removeSelected"
      @press:enter="onPressEnter"
      @sort="sort"
    />

    <ScreenEmptyState v-show="!songs.length">
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
import { eventBus, pluralize } from '@/utils'
import { commonStore, favoriteStore } from '@/stores'
import { downloadService } from '@/services'
import { useSongList } from '@/composables'
import { nextTick, toRef } from 'vue'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'

const {
  SongList,
  SongListControls,
  ControlsToggle,
  songs,
  songList,
  duration,
  selectedSongs,
  showingControls,
  isPhone,
  onPressEnter,
  playAll,
  playSelected,
  toggleControls,
  sort
} = useSongList(toRef(favoriteStore.state, 'songs'), 'favorites')

const allowDownload = toRef(commonStore.state, 'allow_download')

const download = () => downloadService.fromFavorites()
const removeSelected = () => selectedSongs.value.length && favoriteStore.unlike(selectedSongs.value)

let initialized = false

const fetchSongs = async () => {
  await favoriteStore.fetch()
  await nextTick()
  sort()
}

eventBus.on('LOAD_MAIN_CONTENT', async (view: MainViewName) => {
  if (view === 'Favorites' && !initialized) {
    await fetchSongs()
    initialized = true
  }
})
</script>
