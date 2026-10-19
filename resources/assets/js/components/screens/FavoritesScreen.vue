<template>
  <ScreenBase>
    <template #header>
      <ScreenHeader :layout="playables.length === 0 ? 'collapsed' : 'expanded'">
        Your Favorites

        <template #thumbnail>
          <ThumbnailStack :thumbnails="thumbnails" />
        </template>

        <template v-if="playables.length" #meta>
          <span>{{ pluralize(playables, 'item') }}</span>
          <span>{{ duration }}</span>

          <LinkButton v-if="downloadable" class="download" @click.prevent="download"> Download All </LinkButton>
          <LinkButton v-if="canToggleOffline" @click.prevent="toggleOffline">
            {{ allCached ? 'Remove Offline' : 'Make Offline' }}
          </LinkButton>
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

    <PlayableListSkeleton v-if="loading" class="-m-6" role="status" aria-busy="true" aria-label="Loading" />
    <PlayableList
      v-if="playables.length"
      ref="playableList"
      class="-m-6"
      @reorder="onReorder"
      @sort="sort"
      @press:delete="removeSelected"
      @press:enter="onPressEnter"
    />

    <ScreenEmptyState v-else>
      <template #icon>
        <Icon :icon="faHeartBroken" />
      </template>
      No favorites yet.
      <span class="secondary block">
        Click the&nbsp;
        <Icon :icon="faHeart" />&nbsp; icon to mark a song as favorite.
      </span>
    </ScreenEmptyState>
  </ScreenBase>
</template>

<script lang="ts" setup>
import { faHeartBroken } from '@fortawesome/free-solid-svg-icons'
import { faHeart } from '@fortawesome/free-regular-svg-icons'
import { computed, ref, watch } from 'vue'
import { pluralize } from '@/utils/formatters'
import { playableStore } from '@/stores/playableStore'
import { useDownload } from '@/composables/useDownload'
import { useOfflinePlayback } from '@/composables/useOfflinePlayback'
import { useRouter } from '@/composables/useRouter'
import { usePlayableList } from '@/composables/usePlayableList'
import { usePlayableListControls } from '@/composables/usePlayableListControls'
import { usePersistedSort } from '@/composables/usePersistedSort'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'
import PlayableListSkeleton from '@/components/playable/playable-list/PlayableListSkeleton.vue'
import LinkButton from '@/components/ui/form/LinkButton.vue'

const allPlayables = ref<Playable[]>([])

const {
  PlayableList,
  ThumbnailStack,
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
  sort: baseSort,
  config: listConfig,
} = usePlayableList(allPlayables, { type: 'Favorites' })

listConfig.reorderable = true
listConfig.hasCustomOrderSort = true

const { PlayableListControls, config } = usePlayableListControls('Favorites')
const { getSavedSort, saveSort } = usePersistedSort('favorites', 'position')

const { fromFavorites } = useDownload()
const download = () => fromFavorites()
const removeSelected = () => selectedPlayables.value.length && playableStore.undoFavorite(selectedPlayables.value)

const { swReady, allPlayablesCached, hasOfflineCapablePlayables, togglePlayablesOffline } = useOfflinePlayback()
const canToggleOffline = computed(() => swReady.value && hasOfflineCapablePlayables(playables.value))
const allCached = computed(() => allPlayablesCached(playables.value))

const toggleOffline = () => togglePlayablesOffline(playables.value, 'favorites')

let initialized = false
const loading = ref(false)

const fetchFavorites = async () => {
  try {
    loading.value = true
    await playableStore.fetchFavorites()

    const { sortField, sortOrder } = getSavedSort()
    sort(sortField, sortOrder)
  } finally {
    loading.value = false
  }
}

const sort = (field: MaybeArray<PlayableListSortField> | null, order: SortOrder) => {
  listConfig.reorderable = field === 'position'

  saveSort(field, order)

  baseSort(field, order)

  if (field === 'position') {
    // Point directly to the store's array so in-place reorder mutations are reflected
    allPlayables.value = playableStore.state.favorites
  }
}

const onReorder = (target: Playable, placement: Placement) => {
  playableStore.moveFavoritesInList(selectedPlayables.value, target, placement)
}

watch(
  () => playableStore.state.favorites,
  favorites => {
    allPlayables.value = favorites
  },
  { immediate: true },
)

useRouter().onScreenActivated('Favorites', async () => {
  if (!initialized) {
    initialized = true
    await fetchFavorites()
  }
})
</script>
