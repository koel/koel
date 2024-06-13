<template>
  <article>
    <button
      ref="button"
      class="border px-3 rounded-md h-full border-white/10 w-full focus:text-k-highlight text-k-text-secondary active:text-white focus:text-white"
      title="Sort"
      @click.stop="trigger"
    >
      <span class="mr-2">{{ currentFieldLabel }}</span>
      <Icon :icon="order === 'asc' ? faArrowUp : faArrowDown" />
    </button>
    <OnClickOutside @trigger="hide">
      <menu ref="menu" class="context-menu normal-case tracking-normal">
        <li
          v-for="item in items"
          :key="item.label"
          :class="currentlySortedBy(item.field) && 'active'"
          class="cursor-pointer flex justify-between"
          @click="sort(item.field)"
        >
          <span>{{ item.label }}</span>
          <span v-if="currentlySortedBy(item.field)" class="opacity-80">
            <Icon class="" v-if="order === 'asc'" :icon="faArrowUp" />
            <Icon v-else :icon="faArrowDown" />
          </span>
        </li>
      </menu>
    </OnClickOutside>
  </article>
</template>

<script setup lang="ts">
import { faArrowDown, faArrowUp } from '@fortawesome/free-solid-svg-icons'
import { OnClickOutside } from '@vueuse/components'
import { useFloatingUi } from '@/composables'
import { computed, onBeforeUnmount, onMounted, ref, toRefs } from 'vue'

const props = withDefaults(defineProps<{
  field?: PodcastListSortField
  order?: SortOrder
}>(), {
  field: 'last_played_at',
  order: 'asc',
})

const { field: activeField, order } = toRefs(props)

const emit = defineEmits<{ (e: 'sort', field: MaybeArray<PodcastListSortField>): void }>()

const button = ref<HTMLButtonElement>()
const menu = ref<HTMLDivElement>()

const { setup, teardown, trigger, hide } = useFloatingUi(button, menu, {
  placement: 'bottom-end',
  useArrow: false,
  autoTrigger: false
})

const items: { label: string, field: PodcastListSortField }[] = [
  { label: 'Last played', field: 'last_played_at' },
  { label: 'Subscribed', field: 'subscribed_at' },
  { label: 'Title', field: 'title' },
  { label: 'Author', field: 'author' }
]

const currentFieldLabel = computed(() => items.find(item => item.field === activeField.value).label)

const sort = (field: MaybeArray<PodcastListSortField>) => {
  emit('sort', field)
  hide()
}

const currentlySortedBy = (field: PodcastListSortField) => field === activeField.value

onMounted(() => menu.value && setup())
onBeforeUnmount(() => teardown())
</script>
