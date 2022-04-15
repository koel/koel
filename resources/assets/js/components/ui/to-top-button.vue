<template>
  <transition name="fade">
    <div class="to-top-btn-wrapper" v-show="showing">
      <button @click="scrollToTop" title="Scroll to top">
        <i class="fa fa-arrow-circle-up"></i> Top
      </button>
    </div>
  </transition>
</template>

<script lang="ts">
import Vue from 'vue'
import { $ } from '@/utils'

export default Vue.extend({
  data: () => ({
    showing: false
  }),

  methods: {
    scrollToTop (): void {
      $.scrollTo(this.$el.parentElement!, 0, 500, (): void => {
        this.showing = false
      })
    }
  },

  mounted (): void {
    this.$el.parentElement && this.$el.parentElement.addEventListener('scroll', (e: Event): void => {
      this.showing = (e.target as HTMLElement).scrollTop > 64
    })
  }
})
</script>

<style lang="scss">
.to-top-btn-wrapper {
  position: fixed;
  width: 100%;
  bottom: calc(var(--footer-height-mobile) + 26px);
  left: 0;
  text-align: center;
  z-index: 20;
  opacity: 1;
  transition: opacity .5s;

  &.fade-enter, &.fade-leave-to {
    opacity: 0;
  }

  button {
    border-radius: 18px;
    padding: 8px 16px;
    background: rgba(0, 0, 0, .5);
    border: 1px solid var(--color-text-primary);
    color: var(--color-text-primary);

    i {
      margin-right: 4px;
    }
  }
}

@media screen and (min-width: 415px) {
  .to-top-btn-wrapper {
    display: none;
  }
}
</style>
