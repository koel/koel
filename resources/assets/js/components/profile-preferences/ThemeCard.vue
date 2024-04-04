<template>
  <article
    :class="{ selected: theme.selected }"
    :style="thumbnailStyles"
    :title="`Set current theme to ${name}`"
    class="theme h-[96px] bg-center bg-cover relative cursor-pointer rounded-lg overflow-hidden border-2 border-solid border-white/10 transition duration-300 hover:border-white/50]"
  >
    <button
      type="button"
      class="opacity-0 hover:opacity-100 absolute h-full w-full top-0 left-0 flex items-center justify-center text-lg transition-opacity bg-black/20"
      @click="$emit('selected', theme)"
    >
      {{ name }}
    </button>
  </article>
</template>

<script lang="ts" setup>
import { toRefs } from 'vue'
import { slugToTitle } from '@/utils'

const props = defineProps<{ theme: Theme }>()
const { theme } = toRefs(props)

const emit = defineEmits<{ (e: 'selected', theme: Theme): void }>()

const name = theme.value.name ? theme.value.name : slugToTitle(theme.value.id)

const thumbnailStyles: Record<string, string> = {
  'background-color': theme.value.thumbnailColor
}

if (theme.value.thumbnailUrl) {
  thumbnailStyles['background-image'] = `url(${theme.value.thumbnailUrl})`
}
</script>

<style lang="postcss" scoped>
.theme.selected {
  @apply border-white/50;
}
</style>
