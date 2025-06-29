<template>
  <article>
    <button ref="button" class="w-full focus:text-k-highlight" title="Sort" @click.stop="trigger">
      <Icon v-if="sortable" :icon="faSort" />
      <Icon v-else :icon="faEllipsis" />
    </button>
    <OnClickOutside @trigger="hide">
      <menu ref="menu" class="context-menu normal-case tracking-normal">
        <li
          v-for="item in menuItems"
          :key="item.label"
          :class="currentlySortedBy(item.field) && 'active'"
          class="cursor-pointer flex justify-between !pl-3 hover:!bg-white/10"
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
              class="disabled:opacity-20 bg-white h-4 aspect-square rounded checked:border-white/75 checked:border-2 checked:bg-k-highlight"
              type="checkbox"
            >
          </label>
          <span class="flex-1 text-left">{{ item.label }}</span>
          <span class="icon hidden ml-3">
            <Icon v-if="field === 'position'" :icon="faCheck" />
            <Icon v-else-if="order === 'asc'" :icon="faArrowUp" />
            <Icon v-else :icon="faArrowDown" />
          </span>
        </li>
      </menu>
    </OnClickOutside>
  </article>
</template>

<script lang="ts" setup>
import { isEqual } from 'lodash'
import { faArrowDown, faArrowUp, faCheck, faEllipsis, faSort } from '@fortawesome/free-solid-svg-icons'
import { OnClickOutside } from '@vueuse/components'
import { computed, onBeforeUnmount, onMounted, ref, toRefs } from 'vue'
import { useFloatingUi } from '@/composables/useFloatingUi'
import { arrayify } from '@/utils/helpers'
import type { getPlayableCollectionContentType } from '@/utils/typeGuards'
import { usePlayableListColumnVisibility } from '@/composables/usePlayableListColumnVisibility'

const props = withDefaults(defineProps<{
  sortable?: boolean
  field?: MaybeArray<PlayableListSortField> // the current field(s) being sorted by
  order?: SortOrder
  hasCustomOrderSort?: boolean // whether to provide "custom order" sort (like for playlists)
  contentType?: ReturnType<typeof getPlayableCollectionContentType>
}>(), {
  sortable: true,
  field: 'title',
  order: 'asc',
  hasCustomOrderSort: false,
  contentType: 'songs',
})

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

const { field, order, hasCustomOrderSort, contentType } = toRefs(props)

const button = ref<HTMLButtonElement>()
const menu = ref<HTMLDivElement>()

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

  let items: MenuItem[] = [title, album, artist, track, genre, year, time, dateAdded]

  if (contentType.value === 'episodes') {
    items = [title, podcast, author, time, dateAdded]
  } else if (contentType.value === 'mixed') {
    items = [title, albumOrPodcast, artistOrAuthor, time, dateAdded]
  }

  if (hasCustomOrderSort.value) {
    items.push(customOrder)
  }

  return items
})

const { setup, teardown, trigger, hide } = useFloatingUi(button, menu, {
  placement: 'bottom-end',
  useArrow: false,
  autoTrigger: false,
})

const sort = (field: MaybeArray<PlayableListSortField>) => {
  emit('sort', field)
  hide()
}

const currentlySortedBy = (field: MaybeArray<PlayableListSortField>) => isEqual(arrayify(field), arrayify(props.field))

onMounted(() => menu.value && setup())
onBeforeUnmount(() => teardown())
</script>

<style lang="postcss" scoped>
.active {
  @apply bg-k-highlight text-k-text-primary;

  .icon {
    @apply block;
  }
}
</style>
