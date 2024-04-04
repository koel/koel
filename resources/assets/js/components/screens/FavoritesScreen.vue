<template>
  <ScreenBase>
    <template #header>
      <ScreenHeader :layout="songs.length === 0 ? 'collapsed' : headerLayout">
        Songs You Love
        <ControlsToggle v-model="showingControls" />

        <template #thumbnail>
          <ThumbnailStack :thumbnails="thumbnails" />
        </template>

        <template v-if="songs.length" #meta>
          <span>{{ pluralize(songs, 'song') }}</span>
          <span>{{ duration }}</span>

          <a
            v-if="allowDownload"
            class="download"
            role="button"
            title="Download all songs in playlist"
            @click.prevent="download"
          >
            Download All
          </a>
        </template>

        <template #controls>
          <SongListControls
            v-if="songs.length && (!isPhone || showingControls)"
            :config="config"
            @filter="applyFilter"
            @play-all="playAll"
            @play-selected="playSelected"
          />
        </template>
      </ScreenHeader>
    </template>

    <SongListSkeleton v-if="loading" class="-m-6" />
    <SongList
      v-if="songs.length"
      ref="songList"
      class="-m-6"
      @sort="sort"
      @press:delete="removeSelected"
      @press:enter="onPressEnter"
      @scroll-breakpoint="onScrollBreakpoint"
    />

    <ScreenEmptyState v-else>
      <template #icon>
        <Icon :icon="faHeartBroken" />
      </template>
      No favorites yet.
      <span class="secondary d-block">
        Click the&nbsp;
        <Icon :icon="faHeart" />&nbsp;
        icon to mark a song as favorite.
      </span>
    </ScreenEmptyState>
  </ScreenBase>
</template>

<script lang="ts" setup>
import { faHeartBroken } from '@fortawesome/free-solid-svg-icons'
import { faHeart } from '@fortawesome/free-regular-svg-icons'
import { pluralize } from '@/utils'
import { commonStore, favoriteStore } from '@/stores'
import { downloadService } from '@/services'
import { useRouter, useSongList, useSongListControls } from '@/composables'
import { nextTick, ref, toRef } from 'vue'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'
import SongListSkeleton from '@/components/ui/skeletons/SongListSkeleton.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'

const {
  SongList,
  ControlsToggle,
  ThumbnailStack,
  headerLayout,
  songs,
  songList,
  duration,
  thumbnails,
  selectedSongs,
  showingControls,
  isPhone,
  onPressEnter,
  playAll,
  playSelected,
  applyFilter,
  onScrollBreakpoint,
  sort
} = useSongList(toRef(favoriteStore.state, 'songs'), { type: 'Favorites' })

const { SongListControls, config } = useSongListControls('Favorites')

const allowDownload = toRef(commonStore.state, 'allows_download')

const download = () => downloadService.fromFavorites()
const removeSelected = () => selectedSongs.value.length && favoriteStore.unlike(selectedSongs.value)

let initialized = false
const loading = ref(false)

const fetchSongs = async () => {
  loading.value = true
  await favoriteStore.fetch()
  loading.value = false
  await nextTick()
  sort()
}

useRouter().onScreenActivated('Favorites', async () => {
  if (!initialized) {
    initialized = true
    await fetchSongs()
  }
})
</script>
