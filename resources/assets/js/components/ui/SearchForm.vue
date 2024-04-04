<template>
  <form
    id="searchForm"
    role="search"
    class="text-k-text-secondary flex items-stretch border overflow-hidden gap-2 pl-4 pr-0 py-0 rounded-md
    border-solid border-transparent bg-black/20 focus-within:border-white/20 focus-within:bg-black/50
    transition-[border,_background-color] duration-200 ease-in-out"
    @submit.prevent="onSubmit"
  >
    <span class="hidden md:flex opacity-70 items-center">
      <Icon :icon="faSearch" />
    </span>

    <TextInput
      ref="input"
      v-model="q"
      :class="{ dirty: q }"
      :placeholder="placeholder"
      autocorrect="false"
      name="q"
      required
      spellcheck="false"
      type="search"
      class="w-full rounded-none h-[36px] !bg-transparent !text-k-text-primary !placeholder:text-white/50"
      @focus="maybeGoToSearchScreen"
      @input="onInput"
    />

    <button type="submit" title="Search" class="block md:hidden py-0 px-4 bg-white/5 rounded-none">
      <Icon :icon="faSearch" />
    </button>
  </form>
</template>

<script lang="ts" setup>
import isMobile from 'ismobilejs'
import { faSearch } from '@fortawesome/free-solid-svg-icons'
import { ref } from 'vue'
import { debounce } from 'lodash'
import { eventBus } from '@/utils'
import { useRouter } from '@/composables'
import TextInput from '@/components/ui/form/TextInput.vue'

const placeholder = isMobile.any ? 'Search' : 'Press F to search'

const { go } = useRouter()

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
  go('search')
}

const maybeGoToSearchScreen = () => isMobile.any || go('search')

eventBus.on('FOCUS_SEARCH_FIELD', () => {
  input.value?.el?.focus()
  input.value?.el?.select()
})
</script>
