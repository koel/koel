<template>
  <ScreenBase>
    <template #header>
      <ScreenHeader :layout="playables.length === 0 ? 'collapsed' : headerLayout">
        Your Favorites

        <template #thumbnail>
          <ThumbnailStack :thumbnails="thumbnails" />
        </template>

        <template v-if="playables.length" #meta>
          <span>{{ pluralize(playables, 'item') }}</span>
          <span>{{ duration }}</span>

          <a
            v-if="downloadable"
            class="download"
            role="button"
            title="Download all favorites"
            @click.prevent="download"
          >
            Download All
          </a>
          <a v-if="canToggleOffline" role="button" @click.prevent="toggleOffline">
            {{ allCached ? 'Remove Offline' : 'Make Offline' }}
          </a>
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
      @press:delete="removeSelected"
      @press:enter="onPressEnter"
      @swipe="onSwipe"
    />

    <ScreenEmptyState v-else>
      <template #icon>
        <Icon :icon="faHeartBroken" />
      </template>
      No favorites yet.
      <span class="secondary block">
        Click the&nbsp;
        <Icon :icon="faStar" />&nbsp; icon to mark a song as favorite.
      </span>
    </ScreenEmptyState>
  </ScreenBase>
</template>

<script lang="ts" setup>
import { faHeartBroken } from '@fortawesome/free-solid-svg-icons'
import { faStar } from '@fortawesome/free-regular-svg-icons'
import { computed, ref, toRef } from 'vue'
import { pluralize } from '@/utils/formatters'
import { playableStore } from '@/stores/playableStore'
import { useDownload } from '@/composables/useDownload'
import { useOfflinePlayback } from '@/composables/useOfflinePlayback'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useRouter } from '@/composables/useRouter'
import { usePlayableList } from '@/composables/usePlayableList'
import { usePlayableListControls } from '@/composables/usePlayableListControls'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'
import PlayableListSkeleton from '@/components/playable/playable-list/PlayableListSkeleton.vue'

const {
  PlayableList,
  ThumbnailStack,
  headerLayout,
  playables,
  playableList,
  duration,
  downloadable,
  thumbnails,
  selectedPlayables,
  onPressEnter,
  playAll,
  playSelected,
  applyFilter,
  onSwipe,
} = usePlayableList(toRef(playableStore.state, 'favorites'), { type: 'Favorites' })

const { PlayableListControls, config } = usePlayableListControls('Favorites')

const { fromFavorites } = useDownload()
const download = () => fromFavorites()
const removeSelected = () => selectedPlayables.value.length && playableStore.undoFavorite(selectedPlayables.value)

const { swReady, makePlayablesAvailableOffline, removePlayablesOfflineCache, allPlayablesCached } = useOfflinePlayback()
const canToggleOffline = computed(() => swReady.value && playables.value.length > 0)
const allCached = computed(() => allPlayablesCached(playables.value))

const toggleOffline = () => {
  if (allCached.value) {
    removePlayablesOfflineCache(playables.value)
    useMessageToaster().toastSuccess('Removed offline versions for favorites.')
  } else {
    makePlayablesAvailableOffline(playables.value)
    useMessageToaster().toastSuccess(`Making ${pluralize(playables.value, 'song')} available offline…`)
  }
}

let initialized = false
const loading = ref(false)

const fetchFavorites = async () => {
  loading.value = true
  await playableStore.fetchFavorites()
  loading.value = false
}

useRouter().onScreenActivated('Favorites', async () => {
  if (!initialized) {
    initialized = true
    await fetchFavorites()
  }
})
</script>
