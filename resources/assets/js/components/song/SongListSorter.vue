<template>
  <div>
    <button ref="button" title="Sort" @click.stop="trigger">
      <icon :icon="faSort" />
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
          <icon v-if="order === 'asc'" :icon="faArrowDown" />
          <icon v-else :icon="faArrowUp" />
        </span>
      </li>
    </menu>
  </div>
</template>

<script lang="ts" setup>
import { faArrowDown, faArrowUp, faSort } from '@fortawesome/free-solid-svg-icons'
import { onBeforeUnmount, onMounted, ref, toRefs } from 'vue'
import { useFloatingUi } from '@/composables'

const props = defineProps<{ field?: SongListSortField, order?: SortOrder }>()
const { field, order } = toRefs(props)

const emit = defineEmits<{ (e: 'sort', field: SongListSortField): void }>()

const button = ref<HTMLButtonElement>()
const menu = ref<HTMLDivElement>()

const menuItems: { label: string, field: SongListSortField }[] = [
  {
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
  }
]

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
