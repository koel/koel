<template>
  <ScreenBase>
    <template #header>
      <ScreenHeader :disabled="loading" :layout="playables.length ? headerLayout : 'collapsed'">
        Results for <span class="font-thin">{{ decodedQ }}</span>

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
      v-else
      ref="playableList"
      class="-m-6"
      @press:enter="onPressEnter"
      @swipe="onSwipe"
    />
  </ScreenBase>
</template>

<script lang="ts" setup>
import { computed, onMounted, ref, toRef } from 'vue'
import { searchStore } from '@/stores/searchStore'
import { usePlayableList } from '@/composables/usePlayableList'
import { usePlayableListControls } from '@/composables/usePlayableListControls'
import { useRouter } from '@/composables/useRouter'
import { pluralize } from '@/utils/formatters'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import PlayableListSkeleton from '@/components/playable/playable-list/PlayableListSkeleton.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'

const { getRouteParam } = useRouter()
const q = ref('')

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
} = usePlayableList(toRef(searchStore.state, 'playables'), { type: 'Search.Playables' })

const { PlayableListControls, config } = usePlayableListControls('Search.Playables')
const decodedQ = computed(() => decodeURIComponent(q.value))
const loading = ref(false)

searchStore.resetPlayableResultState()

onMounted(async () => {
  q.value = getRouteParam('q') || ''
  if (!q.value) {
    return
  }

  loading.value = true
  await searchStore.playableSearch(q.value)
  loading.value = false
})
</script>
