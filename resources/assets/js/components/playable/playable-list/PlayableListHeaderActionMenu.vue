<template>
  <article>
    <button ref="button" class="w-full focus:text-k-highlight" title="Sort">
      <Icon v-if="sortable" :icon="faSort" />
      <Icon v-else :icon="faEllipsis" />
    </button>
    <Popover ref="popover" :anchor="button" placement="bottom-end" class="context-menu normal-case tracking-normal">
      <menu>
        <li
          v-for="item in menuItems"
          :key="item.label"
          :class="currentlySortedBy(item.field) && 'active'"
          class="cursor-pointer group flex justify-between pl-3! hover:bg-k-highlight! hover:text-k-highlight-fg!"
          @click="sortable && sort(item.field)"
        >
          <label
            v-if="shouldShowColumnVisibilityCheckboxes()"
            class="w-4 mr-2.5 flex items-center"
            @click.stop="item.visibilityToggleable && toggleColumn(item.column!)"
          >
            <input
              :checked="shouldShowColumn(item.column!)"
              :disabled="!item.visibilityToggleable"
              :title="item.visibilityToggleable ? `Click to toggle the ${item.label} column` : ''"
              class="disabled:opacity-20 disabled:cursor-not-allowed bg-k-fg group-hover:border-k-highlight-fg h-4 aspect-square rounded checked:border-k-fg-70 checked:border-2 checked:bg-k-highlight"
              type="checkbox"
            />
          </label>
          <span class="flex-1 text-left">{{ item.label }}</span>
          <span class="icon hidden ml-3">
            <Icon v-if="field === 'position'" :icon="faCheck" />
            <Icon v-else-if="order === 'asc'" :icon="faArrowUp" />
            <Icon v-else :icon="faArrowDown" />
          </span>
        </li>
      </menu>
    </Popover>
  </article>
</template>

<script lang="ts" setup>
import { isEqual } from 'lodash-es'
import { faArrowDown, faArrowUp, faCheck, faEllipsis, faSort } from '@fortawesome/free-solid-svg-icons'
import { computed, ref, toRefs } from 'vue'
import { arrayify } from '@/utils/helpers'
import type { getPlayableCollectionContentType } from '@/utils/typeGuards'
import { usePlayableListColumnVisibility } from '@/composables/usePlayableListColumnVisibility'

import Popover from '@/components/ui/Popover.vue'

const props = withDefaults(
  defineProps<{
    sortable?: boolean
    field?: MaybeArray<PlayableListSortField> // the current field(s) being sorted by
    order?: SortOrder
    hasCustomOrderSort?: boolean // whether to provide "custom order" sort (like for playlists)
    contentType?: ReturnType<typeof getPlayableCollectionContentType>
    collaborative?: boolean
  }>(),
  {
    sortable: true,
    field: 'title',
    order: 'asc',
    hasCustomOrderSort: false,
    contentType: 'songs',
    collaborative: false,
  },
)

const emit = defineEmits<{ (e: 'sort', field: MaybeArray<PlayableListSortField>): void }>()

interface MenuItem {
  column?: PlayableListColumnName
  label: string
  field: MaybeArray<PlayableListSortField>
  visibilityToggleable: boolean
}

const {
  shouldShowColumn,
  toggleColumn,
  isConfigurable: shouldShowColumnVisibilityCheckboxes,
} = usePlayableListColumnVisibility()

const { field, order, hasCustomOrderSort, contentType, collaborative } = toRefs(props)

const button = ref<HTMLButtonElement>()
const popover = ref<InstanceType<typeof Popover>>()

const menuItems = computed(() => {
  const title: MenuItem = { column: 'title', label: 'Title', field: 'title', visibilityToggleable: false }
  const artist: MenuItem = { label: 'Artist', field: 'artist_name', visibilityToggleable: false }
  const author: MenuItem = { label: 'Author', field: 'podcast_author', visibilityToggleable: false }

  const artistOrAuthor: MenuItem = {
    label: 'Artist or Author',
    field: ['artist_name', 'podcast_author'],
    visibilityToggleable: false,
  }

  const album: MenuItem = { column: 'album', label: 'Album', field: 'album_name', visibilityToggleable: true }
  const track: MenuItem = { column: 'track', label: 'Track & Disc', field: 'track', visibilityToggleable: true }
  const time: MenuItem = { column: 'duration', label: 'Time', field: 'length', visibilityToggleable: true }
  const genre: MenuItem = { column: 'genre', label: 'Genre', field: 'genre', visibilityToggleable: true }
  const year: MenuItem = { column: 'year', label: 'Year', field: 'year', visibilityToggleable: true }

  const dateAdded: MenuItem = {
    label: 'Date Added',
    field: 'created_at',
    visibilityToggleable: false,
  }

  const podcast: MenuItem = { column: 'album', label: 'Podcast', field: 'podcast_title', visibilityToggleable: true }

  const albumOrPodcast: MenuItem = {
    column: 'album',
    label: 'Album or Podcast',
    field: ['album_name', 'podcast_title'],
    visibilityToggleable: true,
  }

  const customOrder: MenuItem = { label: 'Custom Order', field: 'position', visibilityToggleable: false }

  const collaborator: MenuItem = {
    column: 'playlist_collaborator',
    label: 'User',
    field: 'collaboration.user.name',
    visibilityToggleable: true,
  }

  const contributedAt: MenuItem = {
    column: 'playlist_added_at',
    label: 'Contributed',
    field: 'collaboration.added_at',
    visibilityToggleable: true,
  }

  let items: MenuItem[] = [title, album, artist, track, genre, year, time, dateAdded]

  if (contentType.value === 'episodes') {
    items = [title, podcast, author, time, dateAdded]
  } else if (contentType.value === 'mixed') {
    items = [title, albumOrPodcast, artistOrAuthor, time, dateAdded]
  }

  if (collaborative.value) {
    items.push(collaborator, contributedAt)
  }

  if (hasCustomOrderSort.value) {
    items.push(customOrder)
  }

  return items
})

const sort = (field: MaybeArray<PlayableListSortField>) => {
  emit('sort', field)
  popover.value?.hide()
}

const currentlySortedBy = (field: MaybeArray<PlayableListSortField>) => isEqual(arrayify(field), arrayify(props.field))
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
.active {
  @apply bg-k-highlight text-k-highlight-fg;

  .icon {
    @apply block;
  }

  input {
    @apply border-k-highlight-fg!;
  }
}
</style>
