<template>
  <div :class="config.sortable ? 'sortable' : 'unsortable'" class="song-list-header flex z-2 bg-k-fg-3 pl-5">
    <SortableColumnHeader
      v-if="shouldShowColumn('track')"
      :active="isSortedBy('track')"
      :order="sortOrder"
      :sortable="isSortable"
      class="track-number"
      data-testid="header-track-number"
      title="Sort by track number"
      label="#"
      @sort="sort('track')"
    />
    <SortableColumnHeader
      :active="isSortedBy('title')"
      :order="sortOrder"
      :sortable="isSortable"
      class="title-artist"
      data-testid="header-title"
      title="Sort by title"
      label="Title"
      @sort="sort('title')"
    />
    <SortableColumnHeader
      v-if="shouldShowColumn('album')"
      :active="isSortedByAlbumOrPodcast"
      :order="sortOrder"
      :sortable="isSortable"
      :title="`Sort by ${contentType === 'episodes' ? 'podcast' : contentType === 'songs' ? 'album' : 'album/podcast'}`"
      class="album"
      data-testid="header-album"
      @sort="
        sort(
          contentType === 'episodes'
            ? 'podcast_title'
            : contentType === 'songs'
              ? 'album_name'
              : ['album_name', 'podcast_title'],
        )
      "
    >
      <template v-if="contentType === 'episodes'">Podcast</template>
      <template v-else-if="contentType === 'songs'">Album</template>
      <template v-else>Album <span class="opacity-50">/</span> Podcast</template>
    </SortableColumnHeader>
    <template v-if="config.collaborative">
      <SortableColumnHeader
        v-if="shouldShowColumn('playlist_collaborator')"
        :active="isSortedBy('collaboration.user.name')"
        :order="sortOrder"
        :sortable="isSortable"
        class="collaborator"
        data-testid="header-collaborator"
        title="Sort by user"
        label="User"
        @sort="sort('collaboration.user.name')"
      />
      <SortableColumnHeader
        v-if="shouldShowColumn('playlist_added_at')"
        :active="isSortedBy('collaboration.added_at')"
        :order="sortOrder"
        :sortable="isSortable"
        class="added-at"
        data-testid="header-contributed-at"
        title="Sort by contributed at"
        label="Contributed"
        @sort="sort('collaboration.added_at')"
      />
    </template>
    <SortableColumnHeader
      v-if="shouldShowColumn('genre')"
      :active="isSortedBy('genre')"
      :order="sortOrder"
      :sortable="isSortable"
      class="genre"
      data-testid="header-genre"
      title="Sort by genre"
      label="Genre"
      @sort="sort('genre')"
    />
    <SortableColumnHeader
      v-if="shouldShowColumn('year')"
      :active="isSortedBy('year')"
      :order="sortOrder"
      :sortable="isSortable"
      class="year"
      data-testid="header-year"
      title="Sort by year"
      label="Year"
      @sort="sort('year')"
    />
    <SortableColumnHeader
      v-if="shouldShowColumn('rating')"
      :active="isSortedBy('rating')"
      :order="sortOrder"
      :sortable="isSortable"
      class="rating"
      data-testid="header-rating"
      title="Sort by rating"
      label="Rating"
      @sort="sort('rating')"
    />
    <SortableColumnHeader
      v-if="shouldShowColumn('duration')"
      :active="isSortedBy('length')"
      :order="sortOrder"
      :sortable="isSortable"
      class="time"
      data-testid="header-length"
      title="Sort by duration"
      label="Time"
      @sort="sort('length')"
    />
    <SortableColumnHeader
      v-if="shouldShowColumn('favorite')"
      :active="isSortedBy('favorite')"
      :order="sortOrder"
      :sortable="isSortable"
      class="favorite"
      data-testid="header-favorite"
      title="Sort by favorite"
      @sort="sort('favorite')"
    >
      <Icon :icon="faHeart"
    /></SortableColumnHeader>
    <span v-if="shouldShowActionMenu" class="extra" data-testid="header-extra">
      <PlayableListHeaderActionMenu
        :sortable="config.sortable"
        :field="sortField"
        :has-custom-order-sort="config.hasCustomOrderSort"
        :order="sortOrder"
        :content-type="contentType"
        :collaborative="config.collaborative"
        @sort="sort"
      />
    </span>
  </div>
</template>

<script setup lang="ts">
import isMobile from 'ismobilejs'
import type { Ref } from 'vue'
import { computed } from 'vue'
import { faHeart } from '@fortawesome/free-solid-svg-icons'
import { arrayify, requireInjection } from '@/utils/helpers'
import { PlayableListConfigKey, PlayableListSortFieldKey, PlayableListSortOrderKey } from '@/config/symbols'
import type { getPlayableCollectionContentType } from '@/utils/typeGuards'
import { useTableColumnVisibility } from '@/composables/useTableColumnVisibility'
import { playableListColumnConfig } from '@/config/tables'

import PlayableListHeaderActionMenu from '@/components/playable/playable-list/PlayableListHeaderActionMenu.vue'
import SortableColumnHeader from '@/components/ui/SortableColumnHeader.vue'

withDefaults(
  defineProps<{
    contentType?: ReturnType<typeof getPlayableCollectionContentType>
  }>(),
  {
    contentType: 'songs',
  },
)

const emit = defineEmits<{
  (e: 'sort', field: MaybeArray<PlayableListSortField>, order: SortOrder): void
}>()

const { shouldShowColumn } = useTableColumnVisibility(playableListColumnConfig)

const [sortField, setSortField] =
  requireInjection<[Ref<MaybeArray<PlayableListSortField>>, Closure]>(PlayableListSortFieldKey)
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

const isSortable = computed(() => Boolean(config.sortable))

const isSortedBy = (field: PlayableListSortField) => sortField.value === field

const isSortedByAlbumOrPodcast = computed(() => {
  const sortFields = arrayify(sortField.value)
  return sortFields[0] === 'album_name' || sortFields[0] === 'podcast_title'
})

// On mobile, the table columns collapse — sorting is the only thing the action
// menu can do. If the list isn't sortable (e.g. the queue), the button would
// just open an inert menu, so drop it entirely.
const shouldShowActionMenu = computed(() => !isMobile.any || config.sortable)
</script>
