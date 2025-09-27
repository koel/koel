<template>
  <HomeScreenBlock>
    <template #header>New Songs</template>

    <PlayableListSkeleton v-if="loading" class="border border-white/5 rounded-lg" />
    <template v-else>
      <PlayableList
        v-if=" playables.length"
        ref="playableList"
        class="border border-white/5 rounded-lg overflow-hidden"
        @press:enter="onPressEnter"
      />
      <p v-else class="text-k-text-secondary">No songs available.</p>
    </template>
  </HomeScreenBlock>
</template>

<script lang="ts" setup>
import { toRef, toRefs } from 'vue'
import { overviewStore } from '@/stores/overviewStore'
import { usePlayableList } from '@/composables/usePlayableList'
import { playback } from '@/services/playbackManager'

import HomeScreenBlock from '@/components/screens/home/HomeScreenBlock.vue'
import PlayableListSkeleton from '@/components/playable/playable-list/PlayableListSkeleton.vue'

const props = withDefaults(defineProps<{ loading?: boolean }>(), { loading: false })
const { loading } = toRefs(props)

const {
  PlayableList,
  playables,
  playableList,
  selectedPlayables,
} = usePlayableList(toRef(overviewStore.state, 'recentlyAddedSongs'), {}, {
  sortable: false,
})

const onPressEnter = () => selectedPlayables.value.length && playback().play(selectedPlayables.value[0])
</script>
