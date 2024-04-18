<template>
  <ScreenBase>
    <template #header>
      <ScreenHeader layout="collapsed">Genres</ScreenHeader>
    </template>

    <ScreenEmptyState v-if="libraryEmpty">
      <template #icon>
        <Icon :icon="faTags" />
      </template>
      No genres found.
      <span class="secondary d-block">
        {{ isAdmin ? 'Have you set up your library yet?' : 'Contact your administrator to set up your library.' }}
      </span>
    </ScreenEmptyState>

    <ul v-if="genres" class="genres text-center">
      <li
        v-for="genre in genres"
        :key="genre.name"
        :class="`level-${getLevel(genre)}`"
        class="rounded-[0.5em] inline-block m-1.5 align-middle overflow-hidden"
      >
        <a
          class="bg-white/15 inline-flex items-center justify-center !text-k-text-secondary
          transition-colors duration-200 ease-in-out hover:!text-k-text-primary hover:bg-k-highlight"
          :href="`/#/genres/${encodeURIComponent(genre.name)}`"
          :title="`${genre.name}: ${pluralize(genre.song_count, 'song')}`"
        >
          <span class="name bg-white/5 px-[0.5em] py-[0.2em] leading-normal">{{ genre.name }}</span>
          <span class="count items-center px-[0.5em] py-[0.2em]">
            {{ genre.song_count }}
          </span>
        </a>
      </li>
    </ul>
    <ul v-else class="text-center">
      <li v-for="i in 20" :key="i" class="inline-block">
        <GenreItemSkeleton />
      </li>
    </ul>
  </ScreenBase>
</template>

<script lang="ts" setup>
import { faTags } from '@fortawesome/free-solid-svg-icons'
import { maxBy, minBy } from 'lodash'
import { computed, onMounted, ref } from 'vue'
import { commonStore, genreStore } from '@/stores'
import { pluralize } from '@/utils'
import { useAuthorization } from '@/composables'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import GenreItemSkeleton from '@/components/ui/skeletons/GenreItemSkeleton.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'

const { isAdmin } = useAuthorization()

const genres = ref<Genre[]>()

const libraryEmpty = computed(() => commonStore.state.song_length === 0)
const mostPopular = computed(() => maxBy(genres.value, 'song_count'))
const leastPopular = computed(() => minBy(genres.value, 'song_count'))

const levels = computed(() => {
  const max = mostPopular.value?.song_count || 1
  const min = leastPopular.value?.song_count || 1
  const range = max - min
  const step = range / 5

  return [min, min + step, min + step * 2, min + step * 3, min + step * 4, max]
})

const getLevel = (genre: Genre) => {
  const index = levels.value.findIndex((level) => genre.song_count <= level)
  return index === -1 ? 5 : index
}

onMounted(async () => {
  if (libraryEmpty.value) return
  genres.value = await genreStore.fetchAll()
})
</script>

<style lang="postcss" scoped>
.genres {
  li {
    font-size: var(--unit);

    &:active {
      @apply scale-95;
    }
  }

  .level-0 {
    --unit: 1rem;
    @apply opacity-80;
  }

  .level-1 {
    --unit: 1.4rem;
    @apply opacity-[84%];
  }

  .level-2 {
    --unit: 1.8rem;
    @apply opacity-[88%];
  }

  .level-3 {
    --unit: 2.2rem;
    @apply opacity-[92%];
  }

  .level-4 {
    --unit: 2.6rem;
    @apply opacity-[96%];
  }

  .level-5 {
    --unit: 3rem;
    @apply opacity-100;
  }
}
</style>
