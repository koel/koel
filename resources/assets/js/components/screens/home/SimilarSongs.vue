<template>
  <ShelfSection v-if="loading || playables.length">
    <template #header>You Might Also Like</template>
    <PlayableCardGridSkeleton v-if="loading" class="-mx-6" role="status" aria-busy="true" aria-label="Loading" />
    <PlayableCardGrid v-else class="-mx-6" :playables />
  </ShelfSection>
</template>

<script lang="ts" setup>
import { toRef, toRefs } from 'vue'
import { overviewStore } from '@/stores/overviewStore'

import ShelfSection from '@/components/ui/ShelfSection.vue'
import PlayableCardGrid from '@/components/screens/home/PlayableCardGrid.vue'
import PlayableCardGridSkeleton from '@/components/screens/home/PlayableCardGridSkeleton.vue'

const props = withDefaults(defineProps<{ loading?: boolean }>(), { loading: false })
const { loading } = toRefs(props)

const playables = toRef(overviewStore.state, 'similarSongs')
</script>
