<template>
  <li :class="cssClasses">
    <span v-if="hasIconSlot" class="w-4">
      <slot name="icon" />
    </span>

    <span v-if="hasIconSlot" class="label max-w-40 overflow-hidden text-ellipsis">
      <slot />
    </span>
    <slot v-else />

    <ul v-if="hasSubMenuItems" class="context-menu submenu">
      <slot name="subMenuItems" />
    </ul>
  </li>
</template>

<script setup lang="ts">
import { useSlots } from 'vue'

const slots = useSlots()

const hasIconSlot = Boolean(slots.icon)
const hasSubMenuItems = Boolean(slots.subMenuItems)

let cssClasses = hasIconSlot ? 'flex items-center gap-3' : ''
if (hasSubMenuItems) {
  cssClasses += ' has-sub'
}
</script>
