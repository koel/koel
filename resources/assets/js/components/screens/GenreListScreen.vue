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
          </div>
        </template>
      </ScreenHeader>
    </template>

    <ScreenEmptyState v-if="libraryEmpty">
      <template #icon>
        <GuitarIcon :size="96" />
      </template>
      No genres found.
      <span v-if="currentUserCan.manageSettings()" class="secondary block">
        Have you set up your library yet?
      </span>
    </ScreenEmptyState>

    <template v-else>
      <ul v-if="!loading" class="genre-list grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-5 gap-3">
        <GenreCard v-for="genre in displayedGenres" :key="genre.id" :genre />
      </ul>

      <ul v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-5 gap-3">
        <GenreCardSkeleton v-for="key in 11" :key />
      </ul>
    </template>
  </ScreenBase>
</template>

<script lang="ts" setup>
import { GuitarIcon } from 'lucide-vue-next'
import { computed, onMounted, provide, ref } from 'vue'
import { commonStore } from '@/stores/commonStore'
import { genreStore } from '@/stores/genreStore'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { preferenceStore as preferences } from '@/stores/preferenceStore'
import { useFuzzySearch } from '@/composables/useFuzzySearch'
import { FilterKeywordsKey } from '@/symbols'
import { orderBy } from 'lodash'
import { usePolicies } from '@/composables/usePolicies'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import GenreCardSkeleton from '@/components/genre/GenreCardSkeleton.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'
import GenreCard from '@/components/genre/GenreCard.vue'
import ListFilter from '@/components/ui/ListFilter.vue'
import GenreListSorter from '@/components/genre/GenreListSorter.vue'

const { currentUserCan } = usePolicies()
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
    return orderBy(
      all,
      [genre => genre.name ? 1 : 0, 'name'],
      ['asc', preferences.genres_sort_order],
    )
  }

  return orderBy(all, preferences.genres_sort_field, preferences.genres_sort_order)
})

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
</style>
