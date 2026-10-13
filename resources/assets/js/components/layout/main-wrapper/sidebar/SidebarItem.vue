<template>
  <li
    :class="{ active }"
    class="relative before:right-0 px-6 before:top-1/4 before:w-[4px] before:h-1/2 before:absolute before:rounded-full before:transition-[box-shadow-sm,background-color] before:ease-in-out before:duration-500"
    data-testid="sidebar-item"
  >
    <a
      :aria-current="active ? 'page' : undefined"
      :aria-disabled="disabledReason ? true : undefined"
      :class="disabledReason ? 'opacity-50 cursor-not-allowed' : 'hover:text-k-fg'"
      :href="disabledReason ? undefined : props.href"
      :title="disabledReason ?? undefined"
      class="flex items-center overflow-x-hidden gap-3 h-11 relative active:pt-0.5 active:pr-0 active:pb-0 active:pl-0.5 text-k-fg-70"
      @click="onClick"
      @dblclick.prevent="onDblClick"
    >
      <span>
        <slot name="icon" />
      </span>

      <span class="flex-1 overflow-hidden">
        <MarqueeText hover-only>
          <slot />
        </MarqueeText>
      </span>

      <span
        v-if="$slots.badge"
        class="shrink-0 rounded-full px-2 py-0.5 text-[0.6875rem] leading-none bg-k-fg-10 text-k-fg-70"
        data-testid="sidebar-item-badge"
      >
        <slot name="badge" />
      </span>
    </a>
  </li>
</template>

<script lang="ts" setup>
import { onBeforeUnmount } from 'vue'
import { useRouter } from '@/composables/useRouter'
import { eventBus } from '@/utils/eventBus'
import MarqueeText from '@/components/ui/MarqueeText.vue'

const props = withDefaults(
  defineProps<{
    href?: string | undefined
    active?: boolean
    disabledReason?: string | null
    onDblclick?: () => void
  }>(),
  {
    active: false,
    disabledReason: null,
  },
)

const { go } = useRouter()

let clickTimer = 0

const navigate = () => {
  if (props.href && !props.disabledReason) {
    go(props.href)
    eventBus.emit('TOGGLE_SIDEBAR')
  }
}

const opensInNewTabOrWindow = (event: MouseEvent) =>
  event.metaKey || event.ctrlKey || event.shiftKey || event.button !== 0

const onClick = (event: MouseEvent) => {
  if (opensInNewTabOrWindow(event)) {
    return
  }

  event.preventDefault()

  if (!props.onDblclick) {
    navigate()
    return
  }

  clearTimeout(clickTimer)
  clickTimer = window.setTimeout(navigate, 150)
}

const onDblClick = () => {
  clearTimeout(clickTimer)
  props.onDblclick?.()
}

onBeforeUnmount(() => clearTimeout(clickTimer))
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
li.active {
  a {
    @apply text-k-fg;
  }

  &::before {
    @apply bg-k-highlight;
    box-shadow: 0 0 40px 10px var(--color-highlight);
  }
}
</style>
