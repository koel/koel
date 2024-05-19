<template>
  <div>
    <button ref="button" class="w-full focus:text-k-highlight" title="Sort" @click.stop="trigger">
      <Icon :icon="faSort" />
    </button>
    <OnClickOutside @trigger="hide">
      <menu ref="menu" class="context-menu normal-case tracking-normal">
        <li
          v-for="item in menuItems"
          :key="item.label"
          :class="isCurrentField(item.field) && 'active'"
          class="cursor-pointer flex justify-between"
          @click="sort(item.field)"
        >
          <span>{{ item.label }}</span>
          <span class="icon hidden ml-3">
          <Icon v-if="field === 'position'" :icon="faCheck" />
          <Icon v-else-if="order === 'asc'" :icon="faArrowDown" />
          <Icon v-else :icon="faArrowUp" />
        </span>
        </li>
      </menu>
    </OnClickOutside>
  </div>
</template>

<script lang="ts" setup>
import { isEqual } from 'lodash'
import { faArrowDown, faArrowUp, faCheck, faSort } from '@fortawesome/free-solid-svg-icons'
import { OnClickOutside } from '@vueuse/components'
import { computed, onBeforeUnmount, onMounted, ref, toRefs } from 'vue'
import { useFloatingUi } from '@/composables'
import { arrayify } from '@/utils'

const props = withDefaults(defineProps<{
  field?: MaybeArray<PlayableListSortField>
  order?: SortOrder
  hasCustomSort?: boolean
  contentType?: 'songs' | 'episodes' | 'mixed'
}>(), {
  field: 'title',
  order: 'asc',
  hasCustomSort: false,
  contentType: 'songs'
})

const { field, order, hasCustomSort, contentType } = toRefs(props)

const emit = defineEmits<{ (e: 'sort', field: MaybeArray<PlayableListSortField>): void }>()

const button = ref<HTMLButtonElement>()
const menu = ref<HTMLDivElement>()

const menuItems = computed<{ label: string, field: MaybeArray<PlayableListSortField> }[]>(() => {
  type MenuItems = { label: string, field: MaybeArray<PlayableListSortField> }

  const title: MenuItems = { label: 'Title', field: 'title' }
  const artist: MenuItems = { label: 'Artist', field: 'artist_name' }
  const author: MenuItems = { label: 'Author', field: 'podcast_author' }
  const artistOrAuthor: MenuItems = { label: 'Artist or Author', field: ['artist_name', 'podcast_author'] }
  const album: MenuItems = { label: 'Album', field: 'album_name' }
  const track: MenuItems = { label: 'Track & Disc', field: 'track' }
  const time: MenuItems = { label: 'Time', field: 'length' }
  const dateAdded: MenuItems = { label: 'Date Added', field: 'created_at' }
  const podcast: MenuItems = { label: 'Podcast', field: 'podcast_title' }
  const albumOrPodcast: MenuItems = { label: 'Album or Podcast', field: ['album_name', 'podcast_title'] }
  const customOrder: MenuItems = { label: 'Custom Order', field: 'position' }

  let items: MenuItems[] = [title, album, artist, track, time, dateAdded]

  if (contentType.value === 'episodes') {
    items = [title, author, podcast, time, dateAdded]
  } else if (contentType.value === 'mixed') {
    items = [title, albumOrPodcast, artistOrAuthor, time, dateAdded]
  }

  if (hasCustomSort.value) {
    items.push(customOrder)
  }

  return items
})

const { setup, teardown, trigger, hide } = useFloatingUi(button, menu, {
  placement: 'bottom-end',
  useArrow: false,
  autoTrigger: false
})

const sort = (field: MaybeArray<PlayableListSortField>) => {
  emit('sort', field)
  hide()
}

const isCurrentField = (field: MaybeArray<PlayableListSortField>) => isEqual(arrayify(field), arrayify(props.field))

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
