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
      v-if="songs.length"
      ref="songList"
      @press:delete="removeSelected"
      @press:enter="onPressEnter"
      @sort="sort"
    />

    <ScreenEmptyState v-else>
      <template v-slot:icon>
        <icon :icon="faHeartBroken"/>
      </template>
      No favorites yet.
      <span class="secondary d-block">
        Click the&nbsp;
        <icon :icon="faHeart"/>&nbsp;
        icon to mark a song as favorite.
      </span>
    </ScreenEmptyState>
  </section>
</template>

<script lang="ts" setup>
import { faHeartBroken } from '@fortawesome/free-solid-svg-icons'
import { faHeart } from '@fortawesome/free-regular-svg-icons'
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
