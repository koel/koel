<template>
  <ScreenBase>
    <template #header>
      <ScreenHeader :layout="playables.length === 0 ? 'collapsed' : headerLayout">
        Results for <span class="font-thin">{{ decodedQ }}</span>
        <ControlsToggle v-model="showingControls" />

        <template #thumbnail>
          <ThumbnailStack :thumbnails="thumbnails" />
        </template>

        <template v-if="playables.length" #meta>
          <span>{{ pluralize(playables, 'item') }}</span>
          <span>{{ duration }}</span>
        </template>

        <template #controls>
          <PlayableListControls
            v-if="playables.length && (!isPhone || showingControls)"
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
      @scroll-breakpoint="onScrollBreakpoint"
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
import PlayableListSkeleton from '@/components/ui/skeletons/PlayableListSkeleton.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'

const { getRouteParam } = useRouter()
const q = ref('')

const {
  PlayableList,
  ControlsToggle,
  ThumbnailStack,
  headerLayout,
  playables,
  playableList,
  thumbnails,
  duration,
  showingControls,
  isPhone,
  onPressEnter,
  playAll,
  playSelected,
  applyFilter,
  onScrollBreakpoint,
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
