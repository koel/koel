<template>
  <HomeScreenBlock>
    <template #header>Something Random</template>

    <PlayableListSkeleton v-if="loading" class="-mx-6 overflow-hidden" />
    <template v-else>
      <PlayableList
        v-if="playables.length"
        ref="playableList"
        class="-mx-6 overflow-hidden"
        @press:enter="onPressEnter"
      />
      <p v-else>No songs available.</p>
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

const { PlayableList, playables, playableList, selectedPlayables } = usePlayableList(
  toRef(overviewStore.state, 'randomSongs'),
  {},
  {
    sortable: false,
  },
)

const onPressEnter = () => selectedPlayables.value.length && playback().play(selectedPlayables.value[0])
</script>
