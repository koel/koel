<template>
  <HomeScreenBlock>
    <template #header>
      Recently Played
      <ViewAllRecentlyPlayedPlayablesButton v-if="playables.length" class="float-right" />
    </template>

    <PlayableCardGridSkeleton v-if="loading" />
    <template v-else>
      <PlayableCardGrid v-if="playables.length" :playables="playables" />
      <p v-else>Nothing played as of late.</p>
    </template>
  </HomeScreenBlock>
</template>

<script lang="ts" setup>
import { toRef, toRefs } from 'vue'
import { overviewStore } from '@/stores/overviewStore'

import HomeScreenBlock from '@/components/screens/home/HomeScreenBlock.vue'
import ViewAllRecentlyPlayedPlayablesButton from '@/components/screens/home/ViewAllRecentlyPlayedPlayablesButton.vue'
import PlayableCardGrid from '@/components/screens/home/PlayableCardGrid.vue'
import PlayableCardGridSkeleton from '@/components/screens/home/PlayableCardGridSkeleton.vue'

const props = withDefaults(defineProps<{ loading?: boolean }>(), { loading: false })
const { loading } = toRefs(props)

const playables = toRef(overviewStore.state, 'recentlyPlayed')
</script>
