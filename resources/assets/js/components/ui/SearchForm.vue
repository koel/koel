<template>
  <div class="side search" id="searchForm" :class="{ showing }" role="search">
    <input
      ref="input"
      v-model="q"
      :class="{ dirty: q }"
      autocorrect="false"
      name="q"
      placeholder="Press F to search"
      spellcheck="false"
      type="search"
      @focus="goToSearchScreen"
      @input="onInput"
    >
  </div>
</template>

<script lang="ts" setup>
import isMobile from 'ismobilejs'
import { ref } from 'vue'
import { debounce } from 'lodash'

import { eventBus } from '@/utils'
import router from '@/router'

const input = ref<HTMLInputElement>()
const q = ref('')
const showing = ref(!isMobile.phone)

const onInput = debounce(() => {
  const _q = q.value.trim()
  _q && eventBus.emit('SEARCH_KEYWORDS_CHANGED', _q)
}, 500)

const goToSearchScreen = () => router.go('/search')

eventBus.on({
  'TOGGLE_SEARCH_FORM': () => (showing.value = !showing.value),

  FOCUS_SEARCH_FIELD () {
    input.value?.focus()
    input.value?.select()
  }
})
</script>

<style lang="scss">
#searchForm {
  @include vertical-center();
  flex: 0 0 256px;
  order: -1;

  input[type="search"] {
    width: 218px;
    margin-top: 0;
  }

  @media only screen and (max-width: 667px) {
    z-index: -1;
    position: absolute;
    left: 0;
    background: var(--color-bg-primary);
    width: 100%;
    padding: 12px;
    top: 0;

    &.showing {
      top: var(--header-height);
      border-bottom: 1px solid rgba(255, 255, 255, .1);
      z-index: 100;
    }

    input[type="search"] {
      width: 100%;
    }
  }

  .desktop & {
    justify-content: flex-end;

    input[type="search"] {
      width: 160px;
    }
  }
}
</style>
