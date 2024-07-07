<template>
  <ExcerptResultBlock>
    <template #header>Podcasts</template>

    <ul v-if="searching" class="results">
      <li v-for="i in 6" :key="i">
        <PodcastCardSkeleton layout="compact" />
      </li>
    </ul>
    <template v-else>
      <ul v-if="podcasts.length" class="results">
        <li v-for="podcast in podcasts" :key="podcast.id">
          <PodcastCard :podcast="podcast" layout="compact" />
        </li>
      </ul>
      <p v-else>None found.</p>
    </template>
  </ExcerptResultBlock>
</template>

<script lang="ts" setup>
import { toRefs } from 'vue'

import ExcerptResultBlock from '@/components/screens/search/ExcerptResultBlock.vue'
import PodcastCardSkeleton from '@/components/ui/skeletons/ArtistAlbumCardSkeleton.vue'
import PodcastCard from '@/components/podcast/PodcastCard.vue'

const props = withDefaults(defineProps<{ podcasts?: Podcast[], searching?: boolean }>(), {
  podcasts: () => [],
  searching: false,
})

const { podcasts, searching } = toRefs(props)
</script>

<style lang="postcss" scoped>
.results {
  @apply grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3;
}
</style>
