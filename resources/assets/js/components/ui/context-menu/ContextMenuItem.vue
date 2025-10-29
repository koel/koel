<template>
  <li
    ref="el"
    :class="cssClasses"
    class="focus:outline-none focus:bg-k-highlight focus:text-k-highlight-fg"
    tabindex="-1"
    @mouseover="focus()"
    @click.prevent="emit('click')"
    @keydown.enter.prevent="emit('click')"
  >
    <span v-if="hasIconSlot" class="w-4">
      <slot name="icon" />
    </span>

    <span class="label flex-1 overflow-hidden max-w-40 text-ellipsis">
      <slot />
    </span>

    <ul v-if="hasSubMenuItems" class="context-menu submenu" tabindex="-1">
      <slot name="subMenuItems" />
    </ul>

    <span v-if="hasSubMenuItems">
      <Icon :icon="faCaretRight" fixed-width />
    </span>
  </li>
</template>

<script setup lang="ts">
import { faCaretRight } from '@fortawesome/free-solid-svg-icons'
import { ref, useSlots } from 'vue'

const emit = defineEmits<{ (e: 'click'): void }>()

const el = ref<HTMLLIElement>()

const focus = () => {
  el.value?.focus()
}

const slots = useSlots()

const hasIconSlot = Boolean(slots.icon)
const hasSubMenuItems = Boolean(slots.subMenuItems)

let cssClasses = hasIconSlot ? 'flex items-center gap-3' : ''

if (hasSubMenuItems) {
  cssClasses += ' has-sub'
}
</script>
