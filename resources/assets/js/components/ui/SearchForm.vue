<template>
  <form
    id="searchForm"
    class="text-k-fg-70 flex items-stretch border border-k-fg-10 overflow-hidden gap-2 pl-4 pr-0 py-0 rounded-md
    bg-k-bg-50 focus-within:border-k-highlight
    transition-[border,_background-color] duration-200 ease-in-out"
    role="search"
    @submit.prevent="onSubmit"
  >
    <span class="hidden md:flex text-k-fg-70 items-center">
      <Icon :icon="faSearch" />
    </span>

    <TextInput
      ref="input"
      v-model="q"
      :class="{ dirty: q }"
      :placeholder="placeholder"
      autocorrect="false"
      class="w-full rounded-none h-[36px] bg-transparent focus-visible:outline-0 !px-2"
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

if (process.env.NODE_ENV !== 'test') {
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
