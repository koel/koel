<template>
  <ExcerptResultBlock>
    <template #header>Radio Stations</template>

    <ul v-if="searching" class="results">
      <li v-for="i in 6" :key="i">
        <RadioStationCardSkeleton layout="compact" />
      </li>
    </ul>
    <template v-else>
      <ul v-if="stations.length" class="results">
        <li v-for="station in stations" :key="station.id">
          <RadioStationCard :station layout="compact" />
        </li>
      </ul>
      <p v-else>None found.</p>
    </template>
  </ExcerptResultBlock>
</template>

<script lang="ts" setup>
import { toRefs } from 'vue'

import ExcerptResultBlock from '@/components/screens/search/ExcerptResultBlock.vue'
import RadioStationCardSkeleton from '@/components/radio/RadioStationCardSkeleton.vue'
import RadioStationCard from '@/components/radio/RadioStationCard.vue'

const props = withDefaults(defineProps<{ stations?: RadioStation[], searching?: boolean }>(), {
  stations: () => [],
  searching: false,
})

const { stations, searching } = toRefs(props)
</script>

<style lang="postcss" scoped>
.results {
  @apply grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3;
}
</style>
