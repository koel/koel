<template>
  <Transition name="fade">
    <button
      v-show="showing"
      ref="el"
      class="sm:hidden block fixed right-[1.8rem] z-20 opacity-100 duration-500 transition-opacity rounded-full py-2 px-4 bg-black/50"
      title="Scroll to top"
      type="button"
      @click="scrollToTop"
    >
      <Icon :icon="faCircleUp" />&nbsp;
      Top
    </button>
  </Transition>
</template>

<script lang="ts" setup>
import { faCircleUp } from '@fortawesome/free-solid-svg-icons'
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

<style lang="postcss" scoped>
button {
  @apply border border-white/50 text-k-text-primary;
  bottom: calc(var(--footer-height) + 26px);

  &.fade-enter, &.fade-leave-to {
    @apply opacity-0;
  }
}
</style>
