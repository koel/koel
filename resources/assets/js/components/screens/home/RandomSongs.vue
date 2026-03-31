<template>
  <HomeScreenBlock>
    <template #header>
      Something Random
      <button
        v-if="playables.length"
        class="float-right text-base text-k-fg-50 hover:text-k-fg"
        title="Refresh"
        @click.prevent="refresh"
      >
        <Icon :icon="faArrowsRotate" />
        <span class="sr-only">Refresh</span>
      </button>
    </template>

    <PlayableCardGridSkeleton v-if="loading || refreshing" class="-mx-6" />
    <template v-else>
      <PlayableCardGrid v-if="playables.length" class="-mx-6" :playables />
      <p v-else>No songs available.</p>
    </template>
  </HomeScreenBlock>
</template>

<script lang="ts" setup>
import { faArrowsRotate } from '@fortawesome/free-solid-svg-icons'
import { ref, toRef, toRefs } from 'vue'
import { overviewStore } from '@/stores/overviewStore'

import HomeScreenBlock from '@/components/screens/home/HomeScreenBlock.vue'
import PlayableCardGrid from '@/components/screens/home/PlayableCardGrid.vue'
import PlayableCardGridSkeleton from '@/components/screens/home/PlayableCardGridSkeleton.vue'

const props = withDefaults(defineProps<{ loading?: boolean }>(), { loading: false })
const { loading } = toRefs(props)

const playables = toRef(overviewStore.state, 'randomSongs')
const refreshing = ref(false)

const refresh = async () => {
  refreshing.value = true

  try {
    await overviewStore.refreshRandomSongs()
  } finally {
    refreshing.value = false
  }
}
</script>
