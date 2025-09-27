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

    <PlayableListSkeleton v-if="searching" class="border border-white/5 rounded-lg" />
    <template v-else>
      <PlayableList
        v-if="displayedPlayables.length"
        ref="playableList"
        class="border border-white/5 rounded-lg overflow-hidden"
        @press:enter="onPressEnter"
      />
      <p v-else>Nothing found.</p>
    </template>
  </ExcerptResultBlock>
</template>

<script lang="ts" setup>
import { computed, toRefs } from 'vue'
import { useRouter } from '@/composables/useRouter'
import { getPlayableCollectionContentType } from '@/utils/typeGuards'
import { usePlayableList } from '@/composables/usePlayableList'
import { playback } from '@/services/playbackManager'

import ExcerptResultBlock from '@/components/screens/search/ExcerptResultBlock.vue'
import Btn from '@/components/ui/form/Btn.vue'
import PlayableListSkeleton from '@/components/playable/playable-list/PlayableListSkeleton.vue'

const props = withDefaults(defineProps<{ playables?: Playable[], query?: string, searching?: boolean }>(), {
  playables: () => [],
  query: '',
  searching: false,
})

const { playables, query, searching } = toRefs(props)

const {
  PlayableList,
  playables: displayedPlayables,
  playableList,
  selectedPlayables,
} = usePlayableList(playables, {}, {
  sortable: false,
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

const { go, url } = useRouter()

const onPressEnter = () => selectedPlayables.value.length && playback().play(selectedPlayables.value[0])
const goToSongResults = () => go(`${url('search.playables')}/?q=${query.value}`)
</script>

<style lang="postcss" scoped>
.results {
  @apply grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3;
}
</style>
