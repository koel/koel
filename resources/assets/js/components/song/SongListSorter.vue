<template>
  <div>
    <button ref="button" title="Sort" @click.stop="trigger">
      <Icon :icon="faSort" />
    </button>
    <menu ref="menu" v-koel-clickaway="hide">
      <li
        v-for="item in menuItems"
        :key="item.label"
        :class="item.field === field && 'active'"
        @click="sort(item.field)"
      >
        <span>{{ item.label }}</span>
        <span class="icon">
          <Icon v-if="field === 'position'" :icon="faCheck" />
          <Icon v-else-if="order === 'asc'" :icon="faArrowDown" />
          <Icon v-else :icon="faArrowUp" />
        </span>
      </li>
    </menu>
  </div>
</template>

<script lang="ts" setup>
import { faArrowDown, faArrowUp, faCheck, faSort } from '@fortawesome/free-solid-svg-icons'
import { computed, onBeforeUnmount, onMounted, ref, toRefs } from 'vue'
import { useFloatingUi } from '@/composables'

const props = withDefaults(defineProps<{ field?: SongListSortField, order?: SortOrder, hasCustomSort?: boolean }>(), {
  field: 'title',
  order: 'asc',
  hasCustomSort: false
})

const { field, order, hasCustomSort } = toRefs(props)

const emit = defineEmits<{ (e: 'sort', field: SongListSortField): void }>()

const button = ref<HTMLButtonElement>()
const menu = ref<HTMLDivElement>()

const menuItems = computed<{ label: string, field: SongListSortField }[]>(() => {
  const items: { label: string, field: SongListSortField }[] = [{
    label: 'Title',
    field: 'title'
  },
    {
      label: 'Artist',
      field: 'artist_name'
    },
    {
      label: 'Album',
      field: 'album_name'
    },
    {
      label: 'Track & Disc',
      field: 'track'
    },
    {
      label: 'Time',
      field: 'length'
    },
    {
      label: 'Date Added',
      field: 'created_at'
    }
  ]

  if (hasCustomSort.value) {
    items.push({
      label: 'Custom Order',
      field: 'position'
    })
  }

  return items
})

const { setup, teardown, trigger, hide } = useFloatingUi(button, menu, {
  placement: 'bottom-end',
  useArrow: false,
  autoTrigger: false
})

const sort = (field: SongListSortField) => {
  emit('sort', field)
  hide()
}

onMounted(() => menu.value && setup())
onBeforeUnmount(() => teardown())
</script>

<style lang="scss" scoped>
button {
  width: 100%;

  &:focus {
    color: var(--color-highlight);
  }
}

menu {
  text-transform: none;
  letter-spacing: 0;

  li {
    cursor: pointer;
    display: flex;
    justify-content: space-between;

    .icon {
      display: none;
    }

    &.active {
      background: var(--color-highlight);
      color: var(--color-text-primary);

      .icon {
        display: block;
      }
    }
  }
}
</style>
