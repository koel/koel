<template>
  <form id="searchForm" role="search" @submit.prevent="onSubmit">
    <span class="icon">
      <icon :icon="faSearch"/>
    </span>

    <input
      ref="input"
      v-model="q"
      :class="{ dirty: q }"
      :placeholder="placeholder"
      autocorrect="false"
      name="q"
      required
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
  router.go('search')
}

const maybeGoToSearchScreen = () => isMobile.any || router.go('search')

eventBus.on('FOCUS_SEARCH_FIELD', () => {
  input.value?.focus()
  input.value?.select()
})
</script>

<style lang="scss">
#searchForm {
  display: flex;
  align-items: stretch;
  color: var(--color-text-secondary);
  background: rgba(0, 0, 0, .2);
  border: 1px solid transparent;
  border-radius: 5px;
  transition: border .3s ease-in-out, .3s background-color ease-in-out;
  overflow: hidden;
  padding: 0 0 0 1rem;
  gap: .5rem;

  .icon {
    display: flex;
    align-items: center;
    opacity: .7;

    @media screen and (max-width: 768px) {
      display: none;
    }
  }

  button {
    display: none;
    padding: 0 1.2rem;
    background: rgba(255, 255, 255, .05);
    border-radius: 0;

    @media screen and (max-width: 768px) {
      display: block;
    }
  }

  &:focus-within {
    border: 1px solid rgba(255, 255, 255, .2);
    background: rgba(0, 0, 0, .5);
  }

  input[type="search"] {
    width: 100%;
    border-radius: 0;
    height: 36px;
    color: var(--color-text-primary);
    background-color: transparent;

    &::placeholder {
      color: rgba(255, 255, 255, .5);
    }
  }
}
</style>
