<template>
  <ShelfSection>
    <template #header>{{ header }}</template>
    <template #actions>
      <ShelfRefreshButton v-if="refresh && playables.length" v-model:refreshing="refreshing" :refresh />
    </template>
    <PlayableCardGridSkeleton v-if="loading" class="-mx-6" role="status" aria-busy="true" aria-label="Loading" />
    <template v-else>
      <PlayableCardGrid v-if="playables.length" :aria-busy="refreshing" class="-mx-6" :playables />
      <p v-else>{{ emptyText }}</p>
    </template>
  </ShelfSection>
</template>

<script lang="ts" setup>
import { computed, ref, toRefs } from 'vue'
import { overviewStore } from '@/stores/overviewStore'

import ShelfSection from '@/components/ui/ShelfSection.vue'
import PlayableCardGrid from '@/components/screens/home/PlayableCardGrid.vue'
import PlayableCardGridSkeleton from '@/components/screens/home/PlayableCardGridSkeleton.vue'
import ShelfRefreshButton from '@/components/screens/home/ShelfRefreshButton.vue'

type OverviewState = typeof overviewStore.state

type SongStateKey = {
  [Key in keyof OverviewState]: OverviewState[Key] extends Song[] ? Key : never
}[keyof OverviewState]

const props = withDefaults(
  defineProps<{
    header: string
    stateKey: SongStateKey
    emptyText: string
    refresh?: () => Promise<unknown>
    loading?: boolean
  }>(),
  { loading: false },
)

const { header, emptyText, refresh, loading } = toRefs(props)

const playables = computed(() => overviewStore.state[props.stateKey])
const refreshing = ref(false)
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
:deep([aria-busy='true']) {
  @apply opacity-70 transition-opacity;
}
</style>
