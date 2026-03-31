<template>
  <HomeScreenBlock v-if="loading || playables.length">
    <template #header>You Might Also Like</template>
    <PlayableCardGridSkeleton v-if="loading" class="-mx-6" />
    <PlayableCardGrid v-else class="-mx-6" :playables />
  </HomeScreenBlock>
</template>

<script lang="ts" setup>
import { toRef, toRefs } from 'vue'
import { overviewStore } from '@/stores/overviewStore'

import HomeScreenBlock from '@/components/screens/home/HomeScreenBlock.vue'
import PlayableCardGrid from '@/components/screens/home/PlayableCardGrid.vue'
import PlayableCardGridSkeleton from '@/components/screens/home/PlayableCardGridSkeleton.vue'

const props = withDefaults(defineProps<{ loading?: boolean }>(), { loading: false })
const { loading } = toRefs(props)

const playables = toRef(overviewStore.state, 'similarSongs')
</script>
