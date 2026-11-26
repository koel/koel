<template>
  <ScreenBase>
    <template #header>
      <ScreenHeader layout="collapsed" :disabled="loading">
        Genres

        <template #controls>
          <div class="flex gap-2">
            <GenreListSorter
              :field="preferences.genres_sort_field"
              :order="preferences.genres_sort_order"
              @sort="sort"
            />

            <ListFilter />

            <button
              v-koel-tooltip
              :disabled="loading"
              class="px-3.5 py-2 border rounded-md border-k-fg-10 disabled:opacity-50"
              title="Reload"
              type="button"
              @click.prevent="fetchGenres"
            >
              <Icon :icon="faRotateRight" />
            </button>
          </div>
        </template>
      </ScreenHeader>
    </template>

    <ScreenEmptyState v-if="libraryEmpty">
      <template #icon>
        <GuitarIcon :size="96" />
      </template>
      No genres found.
      <EmptyLibraryHint />
    </ScreenEmptyState>

    <template v-else>
      <TransitionGroup
        v-if="!loading"
        class="genre-list relative flex flex-wrap items-center gap-2.5"
        name="genre"
        tag="ul"
      >
        <GenreCard
          v-for="genre in displayedGenres"
          :key="genre.id"
          :genre
          :size="getGenreSize(genre, largestSongCount)"
        />
      </TransitionGroup>

      <ul v-else class="flex flex-wrap items-center gap-2.5" role="status" aria-busy="true" aria-label="Loading">
        <GenreCardSkeleton v-for="(width, index) in skeletonWidths" :key="index" :width />
      </ul>
    </template>
  </ScreenBase>
</template>

<script lang="ts" setup>
import { faRotateRight } from '@fortawesome/free-solid-svg-icons'
import { GuitarIcon } from 'lucide-vue-next'
import { computed, onMounted, provide, ref } from 'vue'
import { commonStore } from '@/stores/commonStore'
import { genreStore } from '@/stores/genreStore'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { preferenceStore as preferences } from '@/stores/preferenceStore'
import { useFuzzySearch } from '@/composables/useFuzzySearch'
import { FilterKeywordsKey } from '@/config/symbols'
import { orderBy } from 'lodash-es'
import { getGenreSize, getLargestGenreSongCount } from '@/utils/genreSize'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import GenreCardSkeleton from '@/components/genre/GenreCardSkeleton.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'
import GenreCard from '@/components/genre/GenreCard.vue'
import ListFilter from '@/components/ui/ListFilter.vue'
import GenreListSorter from '@/components/genre/GenreListSorter.vue'
import EmptyLibraryHint from '@/components/ui/EmptyLibraryHint.vue'

const { handleHttpError } = useErrorHandler()

const genres = ref<Genre[]>([])
const keywords = ref('')
const loading = ref(false)

const fuzzy = useFuzzySearch<Genre>(genres, ['name'])

provide(FilterKeywordsKey, keywords)

const displayedGenres = computed(() => {
  const all = keywords.value ? fuzzy.search(keywords.value) : genres.value

  if (preferences.genres_sort_field === 'name') {
    // if sorted by name, ensure 'No Genre' is always on top
    return orderBy(all, [genre => (genre.name ? 1 : 0), 'name'], ['asc', preferences.genres_sort_order])
  }

  return orderBy(all, preferences.genres_sort_field, preferences.genres_sort_order)
})

const largestSongCount = computed(() => getLargestGenreSongCount(genres.value))

const skeletonWidths = [140, 96, 180, 120, 88, 220, 104, 150, 92, 170, 116, 132]

const libraryEmpty = computed(() => commonStore.state.song_length === 0)

const fetchGenres = async () => {
  if (loading.value) {
    return
  }

  try {
    loading.value = true
    genres.value = await genreStore.fetchAll()
  } catch (error: unknown) {
    handleHttpError(error)
  } finally {
    loading.value = false
  }
}

const sort = (field: GenreListSortField, order: SortOrder) => {
  preferences.genres_sort_field = field
  preferences.genres_sort_order = order
}

onMounted(async () => {
  if (libraryEmpty.value) {
    return
  }

  await fetchGenres()
})
</script>

<style lang="postcss">
.genre-list {
  content-visibility: auto;
}

.genre-move,
.genre-enter-active,
.genre-leave-active {
  transition:
    transform 0.25s ease,
    opacity 0.25s ease;
}

.genre-enter-from,
.genre-leave-to {
  opacity: 0;
  transform: scale(0.8);
}

.genre-leave-active {
  position: absolute;
}

@media (prefers-reduced-motion: reduce) {
  .genre-move,
  .genre-enter-active,
  .genre-leave-active {
    transition: none;
  }
}
</style>
