<template>
  <ScreenBase>
    <template #header>
      <ScreenHeader :layout="playables.length === 0 ? 'collapsed' : headerLayout">
        {{ t('screens.favorites') }}

        <template #thumbnail>
          <ThumbnailStack :thumbnails="thumbnails" />
        </template>

        <template v-if="playables.length" #meta>
          <span>{{ itemCountText }}</span>
          <span>{{ duration }}</span>

          <a
            v-if="downloadable"
            class="download"
            role="button"
            :title="t('ui.tooltips.downloadAll')"
            @click.prevent="download"
          >
            {{ t('ui.buttons.downloadAll') }}
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
      {{ t('misc.noFavoritesYet') }}
      <span class="secondary block">
        {{ t('screens.favoritesEmptyHint') }}
      </span>
    </ScreenEmptyState>
  </ScreenBase>
</template>

<script lang="ts" setup>
import { faHeartBroken } from '@fortawesome/free-solid-svg-icons'
import { faStar } from '@fortawesome/free-regular-svg-icons'
import { computed, ref, toRef } from 'vue'
import { useI18n } from 'vue-i18n'
import { playableStore } from '@/stores/playableStore'
import { downloadService } from '@/services/downloadService'
import { useRouter } from '@/composables/useRouter'
import { usePlayableList } from '@/composables/usePlayableList'
import { usePlayableListControls } from '@/composables/usePlayableListControls'

const { t } = useI18n()

const itemCountText = computed(() => {
  const count = playables.value.length
  const itemText = count === 1 ? t('messages.genericItemSingular') : t('messages.genericItemPlural')
  return `${count.toLocaleString()} ${itemText}`
})

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

const download = () => downloadService.fromFavorites()
const removeSelected = () => selectedPlayables.value.length && playableStore.undoFavorite(selectedPlayables.value)

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
