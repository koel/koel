<template>
  <HomeScreenBlock v-if="loading || playables.length">
    <template #header>You Might Also Like</template>

    <PlayableListSkeleton v-if="loading" class="-mx-6 overflow-hidden" />
    <PlayableList v-else ref="playableList" class="-mx-6 overflow-hidden" @press:enter="onPressEnter" />
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
  toRef(overviewStore.state, 'similarSongs'),
  {},
  {
    sortable: false,
  },
)

const onPressEnter = () => selectedPlayables.value.length && playback().play(selectedPlayables.value[0])
</script>
