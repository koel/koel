<template>
  <div
    :class="config.sortable ? 'sortable' : 'unsortable'"
    class="song-list-header flex z-[2] bg-k-bg-secondary pl-5"
  >
    <span
      v-if="shouldShowColumn('track')"
      class="track-number"
      data-testid="header-track-number"
      role="button"
      title="Sort by track number"
      @click="sort('track')"
    >
      #
      <template v-if="config.sortable">
        <Icon v-if="sortField === 'track' && sortOrder === 'asc'" :icon="faCaretUp" class="text-k-highlight" />
        <Icon v-if="sortField === 'track' && sortOrder === 'desc'" :icon="faCaretDown" class="text-k-highlight" />
      </template>
    </span>
    <span
      class="title-artist"
      data-testid="header-title"
      role="button"
      title="Sort by title"
      @click="sort('title')"
    >
      Title
      <template v-if="config.sortable">
        <Icon v-if="sortField === 'title' && sortOrder === 'asc'" :icon="faCaretUp" class="text-k-highlight" />
        <Icon v-if="sortField === 'title' && sortOrder === 'desc'" :icon="faCaretDown" class="text-k-highlight" />
      </template>
    </span>
    <span
      v-if="shouldShowColumn('album')"
      :title="`Sort by ${contentType === 'episodes' ? 'podcast' : (contentType === 'songs' ? 'album' : 'album/podcast')}`"
      class="album"
      data-testid="header-album"
      role="button"
      @click="sort(contentType === 'episodes' ? 'podcast_title' : (contentType === 'songs' ? 'album_name' : ['album_name', 'podcast_title']))"
    >
      <template v-if="contentType === 'episodes'">Podcast</template>
      <template v-else-if="contentType === 'songs'">Album</template>
      <template v-else>Album <span class="opacity-50">/</span> Podcast</template>

      <span v-if="config.sortable" class="ml-2">
        <Icon v-if="sortingByAlbumOrPodcast && sortOrder === 'asc'" :icon="faCaretUp" class="text-k-highlight" />
        <Icon v-if="sortingByAlbumOrPodcast && sortOrder === 'desc'" :icon="faCaretDown" class="text-k-highlight" />
      </span>
    </span>
    <template v-if="config.collaborative">
      <span class="collaborator">User</span>
      <span class="added-at">Added</span>
    </template>
    <span
      v-if="shouldShowColumn('genre')"
      class="genre"
      data-testid="header-genre"
      role="button"
      title="Sort by genre"
      @click="sort('genre')"
    >
      Genre
      <template v-if="config.sortable">
        <Icon v-if="sortField === 'genre' && sortOrder === 'asc'" :icon="faCaretUp" class="text-k-highlight" />
        <Icon v-if="sortField === 'genre' && sortOrder === 'desc'" :icon="faCaretDown" class="text-k-highlight" />
      </template>
    </span>
    <span
      v-if="shouldShowColumn('year')"
      class="year"
      data-testid="header-year"
      role="button"
      title="Sort by year"
      @click="sort('year')"
    >
      Year
      <template v-if="config.sortable">
        <Icon v-if="sortField === 'year' && sortOrder === 'asc'" :icon="faCaretUp" class="text-k-highlight" />
        <Icon v-if="sortField === 'year' && sortOrder === 'desc'" :icon="faCaretDown" class="text-k-highlight" />
      </template>
    </span>
    <span
      v-if="shouldShowColumn('duration')"
      class="time"
      data-testid="header-length"
      role="button"
      title="Sort by duration"
      @click="sort('length')"
    >
      Time
      <template v-if="config.sortable">
        <Icon v-if="sortField === 'length' && sortOrder === 'asc'" :icon="faCaretUp" class="text-k-highlight" />
        <Icon v-if="sortField === 'length' && sortOrder === 'desc'" :icon="faCaretDown" class="text-k-highlight" />
      </template>
    </span>
    <span class="extra">
      <PlayableListHeaderActionMenu
        :sortable="config.sortable"
        :field="sortField"
        :has-custom-order-sort="config.hasCustomOrderSort"
        :order="sortOrder"
        :content-type="contentType"
        @sort="sort"
      />
    </span>
  </div>
</template>

<script setup lang="ts">
import type { Ref } from 'vue'
import { computed } from 'vue'
import { faCaretDown, faCaretUp } from '@fortawesome/free-solid-svg-icons'
import { arrayify, requireInjection } from '@/utils/helpers'
import { PlayableListConfigKey, PlayableListSortFieldKey, PlayableListSortOrderKey } from '@/symbols'
import type { getPlayableCollectionContentType } from '@/utils/typeGuards'
import { usePlayableListColumnVisibility } from '@/composables/usePlayableListColumnVisibility'

import PlayableListHeaderActionMenu from '@/components/playable/playable-list/PlayableListHeaderActionMenu.vue'

withDefaults(defineProps<{
  contentType?: ReturnType<typeof getPlayableCollectionContentType>
}>(), {
  contentType: 'songs',
})

const emit = defineEmits<{
  (e: 'sort', field: MaybeArray<PlayableListSortField>, order: SortOrder): void
}>()

const { shouldShowColumn } = usePlayableListColumnVisibility()

const [sortField, setSortField] = requireInjection<[Ref<MaybeArray<PlayableListSortField>>, Closure]>(PlayableListSortFieldKey)
const [sortOrder, setSortOrder] = requireInjection<[Ref<SortOrder>, Closure]>(PlayableListSortOrderKey)
const [config] = requireInjection<[Partial<PlayableListConfig>]>(PlayableListConfigKey, [{}])

const sort = (field: MaybeArray<PlayableListSortField>) => {
  // there are certain circumstances where sorting is simply disallowed, e.g. in Queue
  if (!config.sortable) {
    return
  }

  setSortField(field)
  setSortOrder(sortOrder.value === 'asc' ? 'desc' : 'asc')

  emit('sort', field, sortOrder.value)
}

const sortingByAlbumOrPodcast = computed(() => {
  const sortFields = arrayify(sortField.value)
  return sortFields[0] === 'album_name' || sortFields[0] === 'podcast_title'
})
</script>
