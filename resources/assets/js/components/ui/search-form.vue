<template>
  <div class="side search" id="searchForm" :class="{ showing: showing }" role="search">
    <input
      type="search"
      :class="{ dirty: q }"
      @input="onInput"
      @focus="goToSearchScreen"
      autocorrect="false"
      placeholder="Press F to search"
      ref="input"
      spellcheck="false"
      name="q"
      v-model="q"
    >
  </div>
</template>

<script lang="ts">
import Vue from 'vue'
import isMobile from 'ismobilejs'
import { debounce } from 'lodash'

import { eventBus } from '@/utils'
import router from '@/router'

export default Vue.extend({
  data: () => ({
    q: '',
    showing: !isMobile.phone
  }),

  methods: {
    onInput: debounce(function (): void {
      // @ts-ignore because of `this`
      const q = this.q.trim()
      if (q) {
        eventBus.emit('SEARCH_KEYWORDS_CHANGED', q)
      }
    }, 500),

    goToSearchScreen: () => router.go('/search')
  },

  created (): void {
    eventBus.on({
      'TOGGLE_SEARCH_FORM': (): void => {
        this.showing = !this.showing
      },

      'FOCUS_SEARCH_FIELD': (): void => {
        (this.$refs.input as HTMLInputElement).focus()
        ;(this.$refs.input as HTMLInputElement).select()
      }
    })
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

  @media only screen and (max-width : 667px) {
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
