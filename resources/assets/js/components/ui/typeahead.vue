<template>
  <div v-koel-clickaway="hideResults" ref="el">
    <input
      type="text"
      :name="config.name"
      :placeholder="config.placeholder || 'No change'"
      v-model="mutatedValue"
      @keydown.down.prevent="down"
      @keydown.up.prevent="up"
      @change="change"
      @keyup="keyup"
      autocomplete="off"
      @dblclick="showingResult = true"
    >
    <ul class="result" v-show="showingResult">
      <li
        v-for="(item, index) in displayedItems"
        :key="index"
        @click="resultClick"
      >
        {{ item[config.displayKey] }}
      </li>
    </ul>
  </div>
</template>

<script lang="ts" setup>
import { computed, nextTick, ref, toRefs } from 'vue'
import { uniq } from 'lodash'
import { $, filterBy } from '@/utils'

interface TypeAheadItem {
  [key: string]: string
}

const props = defineProps<{ config: Record<string, any>, items: TypeAheadItem[], value: string }>()
const { config, items, value } = toRefs(props)

const el = ref(null as unknown as HTMLElement)
const showingResult = ref(false)

const mutatedValue = ref(value.value)
const filter = ref(value.value)
let lastSelectedValue = value.value

const displayedItems = computed(() => uniq(filterBy(items.value, filter.value, config.value.filterKey)))

const down = async () => {
  showingResult.value = true
  await nextTick()
  const selected = el.value.querySelector('.result li.selected')

  if (!selected || !selected.nextElementSibling) {
    // No item selected, or we're at the end of the list.
    // Select the first item now.
    $.addClass(el.value.querySelector('.result li:first-child'), 'selected')
    selected && $.removeClass(selected, 'selected')
  } else {
    $.removeClass(selected, 'selected')
    $.addClass(selected.nextElementSibling, 'selected')
  }

  apply()
  scrollSelectedIntoView(false)
}

const up = async () => {
  showingResult.value = true
  nextTick()
  const selected = el.value.querySelector('.result li.selected')

  if (!selected || !selected.previousElementSibling) {
    $.addClass(el.value.querySelector('.result li:last-child'), 'selected')
    selected && $.removeClass(selected, 'selected')
  } else {
    $.removeClass(selected, 'selected')
    $.addClass(selected.previousElementSibling, 'selected')
  }

  apply()
  scrollSelectedIntoView(true)
}

const keyup = (e: KeyboardEvent) => {
  /**
   * If it's an Up or Down arrow key, stop event bubbling to allow traversing the result dropdown
   */
  if (e.key === 'ArrowUp' || e.key === 'ArrowDown') {
    e.stopPropagation()
    e.preventDefault()
    return
  }

  // If it's an ENTER or TAB key, don't do anything.
  // We've handled ENTER & TAB on keydown.
  if (e.key === 'Enter' || e.key === 'Tab') {
    apply()
    return
  }

  // Hide the typeahead results and reset the value if ESC is pressed.
  if (e.key === 'Escape') {
    mutatedValue.value = lastSelectedValue
    hideResults()
    return
  }

  filter.value = mutatedValue.value
  showingResult.value = true
}

const change = () => apply()

const resultClick = async (e: MouseEvent) => {
  const selected = el.value.querySelector('.result li.selected')
  $.removeClass(selected, 'selected')
  $.addClass(e.target as Element, 'selected')

  await nextTick()
  apply()
  hideResults()
}

const emit = defineEmits(['input'])

const apply = () => {
  const selected = el.value.querySelector<HTMLElement>('.result li.selected')
  mutatedValue.value = (selected && selected.innerText.trim()) || mutatedValue.value
  lastSelectedValue = mutatedValue.value
  emit('input', mutatedValue.value)
}

/**
 * @param  {boolean} alignTop Whether the item should be aligned to top of its container.
 */
const scrollSelectedIntoView = (alignTop: boolean) => {
  const elem = el.value.querySelector<HTMLElement>('.result li.selected')

  if (!elem) {
    return
  }

  const elemRect = elem.getBoundingClientRect()
  const containerRect = elem.offsetParent!.getBoundingClientRect()

  if (elemRect.bottom > containerRect.bottom || elemRect.top < containerRect.top) {
    elem.scrollIntoView(alignTop)
  }
}

const hideResults = () => (showingResult.value = false)
</script>

<style lang="scss" scoped>
.result {
  position: absolute;
  background: rgba(0, 0, 0, .9);
  max-height: 96px;
  border-radius: 0 0 3px 3px;
  width: 100%;
  overflow-y: scroll;
  z-index: 1;

  li {
    padding: 2px 8px;

    &.selected, &:hover {
      background: var(--color-highlight);
      color: var(--color-text-primary);
    }
  }
}
</style>
