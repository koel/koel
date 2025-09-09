<template>
  <ScreenBase>
    <template #header>
      <ScreenHeader :layout="playables.length === 0 ? 'collapsed' : headerLayout">
        Recently Played

        <template #thumbnail>
          <ThumbnailStack :thumbnails="thumbnails" />
        </template>

        <template v-if="playables.length" #meta>
          <span>{{ pluralize(playables, 'item') }}</span>
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

    <PlayableListSkeleton v-if="loading" class="-m-6" />

    <PlayableList
      v-if="playables.length"
      ref="playableList"
      class="-m-6"
      @press:enter="onPressEnter"
      @swipe="onSwipe"
    />

    <ScreenEmptyState v-else>
      <template #icon>
        <Icon :icon="faClock" />
      </template>
      Nothing played recently.
      <span class="secondary block">Start playing to populate this playlist.</span>
    </ScreenEmptyState>
  </ScreenBase>
</template>

<script lang="ts" setup>
import { faClock } from '@fortawesome/free-regular-svg-icons'
import { ref, toRef } from 'vue'
import { pluralize } from '@/utils/formatters'
import { recentlyPlayedStore } from '@/stores/recentlyPlayedStore'
import { useRouter } from '@/composables/useRouter'
import { usePlayableList } from '@/composables/usePlayableList'
import { usePlayableListControls } from '@/composables/usePlayableListControls'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'
import PlayableListSkeleton from '@/components/playable/playable-list/PlayableListSkeleton.vue'

const recentlyPlayedSongs = toRef(recentlyPlayedStore.state, 'playables')

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
} = usePlayableList(recentlyPlayedSongs, { type: 'RecentlyPlayed' }, { sortable: false })

const { PlayableListControls, config } = usePlayableListControls('RecentlyPlayed')

let initialized = false
const loading = ref(false)

useRouter().onScreenActivated('RecentlyPlayed', async () => {
  if (!initialized) {
    loading.value = true
    initialized = true
    await recentlyPlayedStore.fetch()
    loading.value = false
  }
})
</script>
