<template>
  <article>
    <button ref="button" class="w-full focus:text-k-highlight" :title="t('songs.sort')" @click.stop="trigger">
      <Icon v-if="sortable" :icon="faSort" />
      <Icon v-else :icon="faEllipsis" />
    </button>
    <OnClickOutside @trigger="hide">
      <menu ref="menu" class="context-menu normal-case tracking-normal">
        <li
          v-for="item in menuItems"
          :key="item.label"
          :class="currentlySortedBy(item.field) && 'active'"
          class="cursor-pointer group flex justify-between !pl-3 hover:!bg-k-highlight hover:!text-k-highlight-fg"
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
              :title="item.visibilityToggleable ? t('songs.clickToToggleColumn', { label: item.label }) : ''"
              class="disabled:opacity-20 disabled:cursor-not-allowed bg-k-fg group-hover:border-k-highlight-fg h-4
              aspect-square rounded checked:border-k-fg-70 checked:border-2 checked:bg-k-highlight"
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
import { useI18n } from 'vue-i18n'
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

const { t } = useI18n()

const {
  shouldShowColumn,
  toggleColumn,
  isConfigurable: shouldShowColumnVisibilityCheckboxes,
} = usePlayableListColumnVisibility()

const { field, order, hasCustomOrderSort, contentType } = toRefs(props)

const button = ref<HTMLButtonElement>()
const menu = ref<HTMLDivElement>()

const menuItems = computed(() => {
  const title: MenuItem = { column: 'title', label: t('songs.title'), field: 'title', visibilityToggleable: false }
  const artist: MenuItem = { label: t('songs.artist'), field: 'artist_name', visibilityToggleable: false }
  const author: MenuItem = { label: t('songs.author'), field: 'podcast_author', visibilityToggleable: false }

  const artistOrAuthor: MenuItem = {
    label: t('songs.artistOrAuthor'),
    field: ['artist_name', 'podcast_author'],
    visibilityToggleable: false,
  }

  const album: MenuItem = { column: 'album', label: t('songs.album'), field: 'album_name', visibilityToggleable: true }
  const track: MenuItem = { column: 'track', label: t('songs.trackAndDisc'), field: 'track', visibilityToggleable: true }
  const time: MenuItem = { column: 'duration', label: t('songs.time'), field: 'length', visibilityToggleable: true }
  const genre: MenuItem = { column: 'genre', label: t('songs.genre'), field: 'genre', visibilityToggleable: true }
  const year: MenuItem = { column: 'year', label: t('songs.year'), field: 'year', visibilityToggleable: true }

  const dateAdded: MenuItem = {
    label: t('songs.dateAdded'),
    field: 'created_at',
    visibilityToggleable: false,
  }

  const podcast: MenuItem = { column: 'album', label: t('menu.playable.podcast'), field: 'podcast_title', visibilityToggleable: true }

  const albumOrPodcast: MenuItem = {
    column: 'album',
    label: t('songs.albumOrPodcast'),
    field: ['album_name', 'podcast_title'],
    visibilityToggleable: true,
  }

  const customOrder: MenuItem = { label: t('songs.customOrder'), field: 'position', visibilityToggleable: false }

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
  @apply bg-k-highlight text-k-highlight-fg;

  .icon {
    @apply block;
  }

  input {
    @apply border-k-highlight-fg !important;
  }
}
</style>
