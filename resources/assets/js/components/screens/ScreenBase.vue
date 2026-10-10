<template>
  <section class="max-h-full min-h-full w-full flex flex-col transform-gpu overflow-hidden">
    <div
      v-if="backgroundImage"
      class="cover-bg"
      data-testid="cover-bg"
      :style="{ backgroundImage: `url(${backgroundImage})` }"
    />
    <div
      ref="headerHost"
      :class="{ 'scroll-away': attached, revealed: api.revealed.value, sliding }"
      :style="attached ? { transform: `translateY(-${hiddenHeight}px)` } : undefined"
      class="header-host shrink-0"
    >
      <slot name="header" />
    </div>

    <main
      ref="main"
      :class="scrollsItself ? 'scrolls-itself' : 'scroll-mask-y'"
      class="flex flex-col b-16 md:b-6 flex-1 place-content-start"
    >
      <ScreenHeaderSpace v-if="contentScrollsInMain" />
      <HookSlot :context="{ screen: getCurrentScreen() }" name="screen.header" />
      <slot />
    </main>
  </section>
</template>

<script lang="ts" setup>
import { computed, onMounted, provide, ref } from 'vue'
import { useRouter } from '@/composables/useRouter'
import { useScrollAwayHeader } from '@/composables/useScrollAwayHeader'
import { ScrollAwayHeaderKey } from '@/config/symbols'

import HookSlot from '@/components/utils/HookSlot.vue'
import ScreenHeaderSpace from '@/components/ui/ScreenHeaderSpace.vue'

const props = withDefaults(
  defineProps<{
    backgroundImage?: string
    /** The screen lays out and scrolls its own content, edge to edge. */
    scrollsItself?: boolean
  }>(),
  {
    backgroundImage: undefined,
    scrollsItself: false,
  },
)

const { getCurrentScreen } = useRouter()

const headerHost = ref<HTMLElement>()
const main = ref<HTMLElement>()
const { attached, hiddenHeight, scrollerCount, api } = useScrollAwayHeader(headerHost)
const sliding = api.sliding
const contentScrollsInMain = computed(() => !props.scrollsItself && scrollerCount.value === 1)

provide(ScrollAwayHeaderKey, props.scrollsItself ? null : api)

onMounted(() => !props.scrollsItself && main.value && api.attachScroller(main.value))
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
main {
  -ms-overflow-style: -ms-autohiding-scrollbar;

  &:not(.scrolls-itself) {
    @apply overflow-scroll p-6;
  }

  &.scrolls-itself {
    @apply overflow-hidden min-h-0;
  }
}

.header-host.scroll-away {
  @apply absolute inset-x-0 top-0 z-10;

  &.revealed {
    @apply shadow-lg backdrop-blur-2xl backdrop-saturate-150;
    background-color: color-mix(in srgb, color-mix(in srgb, var(--color-fg) 3%, var(--color-bg)) 75%, transparent);
  }

  &.sliding {
    @apply transition-transform duration-200 ease-out;
  }
}

.cover-bg {
  @apply absolute bg-cover bg-center pointer-events-none;
  inset: -32px;
  filter: blur(24px);
  -webkit-mask-image: linear-gradient(to bottom, rgba(0, 0, 0, 0.3) 0%, transparent 50%);
  mask-image: linear-gradient(to bottom, rgba(0, 0, 0, 0.3) 0%, transparent 50%);
}
</style>
