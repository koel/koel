<template>
  <form
    id="searchForm"
    class="relative text-k-fg-70 flex items-stretch border border-k-fg-10 overflow-hidden gap-2 py-0 rounded-md
    bg-k-bg-50 focus-within:border-k-highlight
    transition-[border,_background-color] duration-200 ease-in-out"
    role="search"
    @submit.prevent="onSubmit"
  >
    <span class="hidden md:flex absolute h-full text-k-fg-70 px-4 items-center pointer-events-none">
      <Icon :icon="faSearch" />
    </span>

    <TextInput
      ref="input"
      v-model="q"
      :class="{ dirty: q }"
      :placeholder="placeholder"
      autocorrect="false"
      class="flex-1 rounded-none border-0 bg-transparent focus-visible:outline-0 md:pl-11"
      name="q"
      required
      spellcheck="false"
      type="search"
      @focus="maybeGoToSearchScreen"
      @input="onInput"
    />

    <button class="block md:hidden py-0 px-4 bg-k-fg-5 rounded-none" title="Search" type="submit">
      <Icon :icon="faSearch" />
    </button>
  </form>
</template>

<script lang="ts" setup>
import isMobile from 'ismobilejs'
import { faSearch } from '@fortawesome/free-solid-svg-icons'
import { ref } from 'vue'
import { debounce } from 'lodash'
import { eventBus } from '@/utils/eventBus'
import { useRouter } from '@/composables/useRouter'

import TextInput from '@/components/ui/form/TextInput.vue'

const placeholder = isMobile.any ? 'Search' : 'Press F to search'

const { go, url } = useRouter()

const input = ref<InstanceType<typeof TextInput>>()
const q = ref('')

let onInput = () => {
  const _q = q.value.trim()
  _q && eventBus.emit('SEARCH_KEYWORDS_CHANGED', _q)
}

if (!window.RUNNING_UNIT_TESTS) {
  onInput = debounce(onInput, 500)
}

const onSubmit = () => {
  eventBus.emit('TOGGLE_SIDEBAR')
  go(url('search'))
}

const maybeGoToSearchScreen = () => isMobile.any || go(url('search'))

eventBus.on('FOCUS_SEARCH_FIELD', () => {
  input.value?.el?.focus()
  input.value?.el?.select()
})
</script>
