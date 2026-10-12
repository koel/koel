<template>
  <ShelfSection>
    <template #header>Recently Played</template>
    <template #actions>
      <ViewAllRecentlyPlayedPlayablesButton v-if="playables.length" />
    </template>
    <PlayableCardGridSkeleton v-if="loading" class="-mx-6" role="status" aria-busy="true" aria-label="Loading" />
    <template v-else>
      <PlayableCardGrid v-if="playables.length" class="-mx-6" :playables />
      <p v-else>Nothing played as of late.</p>
    </template>
  </ShelfSection>
</template>

<script lang="ts" setup>
import { toRef, toRefs } from 'vue'
import { overviewStore } from '@/stores/overviewStore'

import ShelfSection from '@/components/ui/ShelfSection.vue'
import ViewAllRecentlyPlayedPlayablesButton from '@/components/screens/home/ViewAllRecentlyPlayedPlayablesButton.vue'
import PlayableCardGrid from '@/components/screens/home/PlayableCardGrid.vue'
import PlayableCardGridSkeleton from '@/components/screens/home/PlayableCardGridSkeleton.vue'

const props = withDefaults(defineProps<{ loading?: boolean }>(), { loading: false })
const { loading } = toRefs(props)

const playables = toRef(overviewStore.state, 'recentlyPlayed')
</script>
