<template>
  <ExcerptResultBlock>
    <template #header>
      {{ headingText }}

      <Btn
        v-if="playables.length && !searching"
        data-testid="view-all-songs-btn"
        highlight
        rounded
        small
        @click.prevent="goToSongResults"
      >
        View All
      </Btn>
    </template>

    <ul v-if="searching" class="results">
      <li v-for="i in 6" :key="i">
        <PlayableCardSkeleton />
      </li>
    </ul>
    <template v-else>
      <ul v-if="playables.length" class="results">
        <li v-for="playable in playables" :key="playable.id">
          <PlayableCard :playable />
        </li>
      </ul>
      <p v-else>None found.</p>
    </template>
  </ExcerptResultBlock>
</template>

<script lang="ts" setup>
import { computed, toRefs } from 'vue'
import { useRouter } from '@/composables/useRouter'
import { getPlayableCollectionContentType } from '@/utils/typeGuards'

import PlayableCardSkeleton from '@/components/playable/PlayableCardSkeleton.vue'
import ExcerptResultBlock from '@/components/screens/search/ExcerptResultBlock.vue'
import PlayableCard from '@/components/playable/PlayableCard.vue'
import Btn from '@/components/ui/form/Btn.vue'

const props = withDefaults(defineProps<{ playables?: Playable[], query?: string, searching?: boolean }>(), {
  playables: () => [],
  query: '',
  searching: false,
})

const headingText = computed(() => {
  switch (getPlayableCollectionContentType(props.playables)) {
    case 'episodes':
      return 'Episodes'
    case 'songs':
      return 'Songs'
    default:
      return 'Songs & Episodes'
  }
})

const { playables, query, searching } = toRefs(props)
const { go, url } = useRouter()

const goToSongResults = () => go(`${url('search.playables')}/?q=${query.value}`)
</script>

<style lang="postcss" scoped>
.results {
  @apply grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3;
}
</style>
