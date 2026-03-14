<template>
  <li
    :class="{ active }"
    class="relative before:right-0 px-6 before:top-1/4 before:w-[4px] before:h-1/2 before:absolute before:rounded-full before:transition-[box-shadow,_background-color] before:ease-in-out before:duration-500"
    data-testid="sidebar-item"
  >
    <a
      :href="props.href"
      class="flex items-center overflow-x-hidden gap-3 h-11 relative active:pt-0.5 active:pr-0 active:pb-0 active:pl-0.5 text-k-fg-70 hover:text-k-fg"
      @click.prevent="onClick"
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
    </a>
  </li>
</template>

<script lang="ts" setup>
import { useRouter } from '@/composables/useRouter'
import { eventBus } from '@/utils/eventBus'
import MarqueeText from '@/components/ui/MarqueeText.vue'

const props = withDefaults(
  defineProps<{
    href?: string | undefined
    active?: boolean
  }>(),
  {
    active: false,
  },
)

const emit = defineEmits<{ dblclick: [] }>()

const { go } = useRouter()

let clickTimer = 0

const navigate = () => {
  if (props.href) {
    go(props.href)
    eventBus.emit('TOGGLE_SIDEBAR')
  }
}

const onClick = () => {
  clearTimeout(clickTimer)
  clickTimer = window.setTimeout(navigate, 150)
}

const onDblClick = () => {
  clearTimeout(clickTimer)
  emit('dblclick')
}
</script>

<style lang="postcss" scoped>
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
