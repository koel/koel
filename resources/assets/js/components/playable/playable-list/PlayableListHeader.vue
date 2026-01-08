<template>
  <div
    :class="config.sortable ? 'sortable' : 'unsortable'"
    class="song-list-header flex z-[2] bg-k-fg-3 pl-5"
  >
    <span
      v-if="shouldShowColumn('track')"
      class="track-number"
      data-testid="header-track-number"
      role="button"
      :title="t('ui.tooltips.sortByTrack')"
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
      :title="t('ui.tooltips.sortByTitle')"
      @click="sort('title')"
    >
      {{ t('songs.title') }}
      <template v-if="config.sortable">
        <Icon v-if="sortField === 'title' && sortOrder === 'asc'" :icon="faCaretUp" class="text-k-highlight" />
        <Icon v-if="sortField === 'title' && sortOrder === 'desc'" :icon="faCaretDown" class="text-k-highlight" />
      </template>
    </span>
    <span
      v-if="shouldShowColumn('album')"
      :title="contentType === 'episodes' ? t('ui.tooltips.sortByPodcast') : (contentType === 'songs' ? t('ui.tooltips.sortByAlbum') : t('ui.tooltips.sortByAlbum'))"
      class="album"
      data-testid="header-album"
      role="button"
      @click="sort(contentType === 'episodes' ? 'podcast_title' : (contentType === 'songs' ? 'album_name' : ['album_name', 'podcast_title']))"
    >
      <template v-if="contentType === 'episodes'">{{ t('menu.playable.podcast') }}</template>
      <template v-else-if="contentType === 'songs'">{{ t('songs.album') }}</template>
      <template v-else>{{ t('songs.album') }} <span class="opacity-50">/</span> {{ t('menu.playable.podcast') }}</template>

      <span v-if="config.sortable" class="ml-2">
        <Icon v-if="sortingByAlbumOrPodcast && sortOrder === 'asc'" :icon="faCaretUp" class="text-k-highlight" />
        <Icon v-if="sortingByAlbumOrPodcast && sortOrder === 'desc'" :icon="faCaretDown" class="text-k-highlight" />
      </span>
    </span>
    <template v-if="config.collaborative">
      <span class="collaborator">{{ t('songs.user') }}</span>
      <span class="added-at">{{ t('songs.added') }}</span>
    </template>
    <span
      v-if="shouldShowColumn('genre')"
      class="genre"
      data-testid="header-genre"
      role="button"
      :title="t('ui.tooltips.sortByGenre')"
      @click="sort('genre')"
    >
      {{ t('songs.genre') }}
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
      :title="t('ui.tooltips.sortByYear')"
      @click="sort('year')"
    >
      {{ t('songs.year') }}
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
      :title="t('ui.tooltips.sortByDuration')"
      @click="sort('length')"
    >
      {{ t('songs.time') }}
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
import { useI18n } from 'vue-i18n'
import { faCaretDown, faCaretUp } from '@fortawesome/free-solid-svg-icons'
import { arrayify, requireInjection } from '@/utils/helpers'
import { PlayableListConfigKey, PlayableListSortFieldKey, PlayableListSortOrderKey } from '@/symbols'
import type { getPlayableCollectionContentType } from '@/utils/typeGuards'
import { usePlayableListColumnVisibility } from '@/composables/usePlayableListColumnVisibility'

import PlayableListHeaderActionMenu from '@/components/playable/playable-list/PlayableListHeaderActionMenu.vue'

const { t } = useI18n()

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
