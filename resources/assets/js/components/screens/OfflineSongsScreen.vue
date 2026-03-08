<template>
  <ScreenBase>
    <template #header>
      <ScreenHeader :layout="playables.length === 0 ? 'collapsed' : headerLayout">
        Available Offline

        <template #thumbnail>
          <ThumbnailStack :thumbnails="thumbnails" />
        </template>

        <template v-if="playables.length" #meta>
          <span>{{ pluralize(playables, 'song') }}</span>
          <span>{{ duration }}</span>
        </template>

        <template #controls>
          <PlayableListControls
            v-if="playables.length"
            :config
            @filter="applyFilter"
            @play-all="playAll"
            @play-selected="playSelected"
          />
        </template>
      </ScreenHeader>
    </template>

    <PlayableList
      v-if="playables.length"
      ref="playableList"
      class="-m-6"
      @press:enter="onPressEnter"
      @swipe="onSwipe"
    />

    <ScreenEmptyState v-else>
      <template #icon>
        <Icon :icon="faCloudArrowDown" />
      </template>
      No songs available offline.
      <span class="secondary block"> Right-click a song and select "Make Available Offline" to cache it. </span>
    </ScreenEmptyState>
  </ScreenBase>
</template>

<script lang="ts" setup>
import { faCloudArrowDown } from '@fortawesome/free-solid-svg-icons'
import { computed } from 'vue'
import { pluralize } from '@/utils/formatters'
import { playableStore } from '@/stores/playableStore'
import { useOfflinePlayback } from '@/composables/useOfflinePlayback'
import { usePlayableList } from '@/composables/usePlayableList'
import { usePlayableListControls } from '@/composables/usePlayableListControls'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'

const { cachedSongIds } = useOfflinePlayback()

const offlineSongs = computed(() => {
  const songs: Playable[] = []

  for (const id of cachedSongIds.value) {
    const song = playableStore.byId(id)
    if (song) songs.push(song)
  }

  return songs
})

const {
  PlayableList,
  ThumbnailStack,
  headerLayout,
  playables,
  playableList,
  thumbnails,
  duration,
  onPressEnter,
  playAll,
  playSelected,
  applyFilter,
  onSwipe,
} = usePlayableList(offlineSongs, { type: 'OfflineSongs' }, { sortable: true })

const { PlayableListControls, config } = usePlayableListControls('OfflineSongs')
</script>
