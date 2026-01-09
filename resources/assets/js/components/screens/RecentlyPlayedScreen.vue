<template>
  <ScreenBase>
    <template #header>
      <ScreenHeader :layout="playables.length === 0 ? 'collapsed' : headerLayout">
        {{ t('screens.recentlyPlayed') }}

        <template #thumbnail>
          <ThumbnailStack :thumbnails="thumbnails" />
        </template>

        <template v-if="playables.length" #meta>
          <span>{{ itemCountText }}</span>
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
      {{ t('emptyStates.recentlyPlayedEmpty') }}
      <span class="secondary block">{{ t('emptyStates.recentlyPlayedEmptyHint') }}</span>
    </ScreenEmptyState>
  </ScreenBase>
</template>

<script lang="ts" setup>
import { faClock } from '@fortawesome/free-regular-svg-icons'
import { computed, ref, toRef } from 'vue'
import { useI18n } from 'vue-i18n'
import { recentlyPlayedStore } from '@/stores/recentlyPlayedStore'
import { useRouter } from '@/composables/useRouter'
import { usePlayableList } from '@/composables/usePlayableList'
import { usePlayableListControls } from '@/composables/usePlayableListControls'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'
import PlayableListSkeleton from '@/components/playable/playable-list/PlayableListSkeleton.vue'

const { t } = useI18n()
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

const itemCountText = computed(() => {
  const count = playables.value.length
  const itemText = count === 1 ? t('messages.genericItemSingular') : t('messages.genericItemPlural')
  return `${count.toLocaleString()} ${itemText}`
})

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
