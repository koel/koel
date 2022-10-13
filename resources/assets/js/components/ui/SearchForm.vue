<template>
  <form id="searchForm" role="search" @submit.prevent="onSubmit">
    <input
      ref="input"
      v-model="q"
      :class="{ dirty: q }"
      autocorrect="false"
      name="q"
      :placeholder="placeholder"
      spellcheck="false"
      type="search"
      @focus="maybeGoToSearchScreen"
      @input="onInput"
    >
    <button type="submit" title="Search">
      <icon :icon="faSearch"/>
    </button>
  </form>
</template>

<script lang="ts" setup>
import isMobile from 'ismobilejs'
import { faSearch } from '@fortawesome/free-solid-svg-icons'
import { ref } from 'vue'
import { debounce } from 'lodash'
import { eventBus, requireInjection } from '@/utils'
import { RouterKey } from '@/symbols'

const placeholder = isMobile.any ? 'Search' : 'Press F to search'

const router = requireInjection(RouterKey)

const input = ref<HTMLInputElement>()
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
  maybeGoToSearchScreen()
}

const maybeGoToSearchScreen = () => isMobile.any || router.go('search')

eventBus.on({
  FOCUS_SEARCH_FIELD: () => {
    input.value?.focus()
    input.value?.select()
  }
})
</script>

<style lang="scss">
#searchForm {
  display: flex;
  align-items: stretch;
  color: var(--color-text-secondary);
  border: 1px solid transparent;
  border-radius: 5px;
  transition: border .2s ease-in-out;
  overflow: hidden;

  button {
    display: none;
    padding: 0 1.5rem;
    background: rgba(255, 255, 255, .05);
    border-radius: 0;

    @media screen and (max-width: 768px) {
      display: block;
    }
  }

  &:focus-within {
    border: 1px solid rgba(255, 255, 255, .2);
  }

  input[type="search"] {
    width: 100%;
    border-radius: 0;
    height: 36px;
    background: rgba(0, 0, 0, .2);
    transition: .3s background-color;
    padding: 0 1rem;
    color: var(--color-text-primary);

    &:focus {
      background: rgba(0, 0, 0, .5);
    }

    &::placeholder {
      color: rgba(255, 255, 255, .5);
    }
  }
}
</style>
