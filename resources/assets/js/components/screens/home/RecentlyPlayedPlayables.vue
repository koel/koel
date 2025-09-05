<template>
  <HomeScreenBlock>
    <template #header>
      Recently Played
      <ViewAllRecentlyPlayedPlayablesButton v-if="playables.length" class="float-right" />
    </template>

    <ol v-if="loading" class="space-y-3">
      <li v-for="i in 3" :key="i">
        <PlayableCardSkeleton />
      </li>
    </ol>
    <template v-else>
      <ol v-if="playables.length" class="space-y-3">
        <li v-for="playable in playables" :key="playable.id">
          <PlayableCard :playable />
        </li>
      </ol>
      <p v-else class="text-k-text-secondary">No songs played as of late.</p>
    </template>
  </HomeScreenBlock>
</template>

<script lang="ts" setup>
import { toRef, toRefs } from 'vue'
import { overviewStore } from '@/stores/overviewStore'

import PlayableCard from '@/components/playable/PlayableCard.vue'
import PlayableCardSkeleton from '@/components/playable/PlayableCardSkeleton.vue'
import HomeScreenBlock from '@/components/screens/home/HomeScreenBlock.vue'
import ViewAllRecentlyPlayedPlayablesButton from '@/components/screens/home/ViewAllRecentlyPlayedPlayablesButton.vue'

const props = withDefaults(defineProps<{ loading?: boolean }>(), { loading: false })
const { loading } = toRefs(props)

const playables = toRef(overviewStore.state, 'recentlyPlayed')
</script>
