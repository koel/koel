<template>
  <Transition name="fade">
    <button v-show="showing" ref="el" title="Scroll to top" type="button" @click="scrollToTop">
      <i class="fa fa-arrow-circle-up"/> Top
    </button>
  </Transition>
</template>

<script lang="ts" setup>
import { onMounted, ref } from 'vue'
import { $ } from '@/utils'

const el = ref<HTMLElement>()
const showing = ref(false)

const scrollToTop = () => $.scrollTo(el.value?.parentElement!, 0, 500, () => (showing.value = false))

onMounted(() => {
  el.value?.parentElement?.addEventListener('scroll', event => {
    showing.value = (event.target as HTMLElement).scrollTop > 64
  })
})
</script>

<style lang="scss" scoped>
button {
  &.fade-enter, &.fade-leave-to {
    opacity: 0;
  }

  position: fixed;
  bottom: calc(var(--footer-height-mobile) + 26px);
  right: 1.8rem;
  z-index: 20;
  opacity: 1;
  transition: opacity .5s;
  border-radius: 9999px;
  padding: 8px 16px;
  background: rgba(0, 0, 0, .5);
  border: 1px solid var(--color-text-primary);
  color: var(--color-text-primary);

  i {
    margin-right: 4px;
  }
}

@media screen and (min-width: 415px) {
  button {
    display: none;
  }
}
</style>
