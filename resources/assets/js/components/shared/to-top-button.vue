<template>
  <transition name="fade">
    <div class="to-top-btn-wrapper" v-show="showing">
      <button @click="scrollToTop">
        <i class="fa fa-arrow-circle-up"/> Top
      </button>
    </div>
  </transition>
</template>

<script>
import { $ } from '@/utils'

export default {
  data () {
    return {
      showing: false
    }
  },

  methods: {
    scrollToTop () {
      $.scrollTo(this.$el.parentNode, 0, 500, () => {
        this.showing = false
      })
    }
  },

  mounted () {
    this.$el.parentNode.addEventListener('scroll', e => {
      this.showing = e.target.scrollTop > 64
    })
  }
}
</script>

<style lang="scss">
@import "../../../sass/partials/_vars.scss";
@import "../../../sass/partials/_mixins.scss";

.to-top-btn-wrapper {
  position: fixed;
  width: 100%;
  bottom: $footerHeightMobile + 26px;
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
    border: 1px solid $colorMainText;

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
