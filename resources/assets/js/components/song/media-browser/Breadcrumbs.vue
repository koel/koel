<template>
  <ul class="text-base" :class="disabled && 'disabled'">
    <li v-for="crumb in crumbs" :key="String(crumb.path)" class="inline-block">
      <a
        v-if="crumb.path !== null"
        :href="url('media-browser', { path: crumb.path })"
        class="text-k-text-secondary font-normal"
      >
        {{ crumb.name }}
      </a>
      <span v-else>
        {{ crumb.name }}
      </span>
    </li>
  </ul>
</template>

<script setup lang="ts">
import { useRouter } from '@/composables/useRouter'
import { computed, toRefs } from 'vue'
import { mediaBrowser } from '@/services/mediaBrowser'

const props = withDefaults(defineProps<{ path: string, disabled?: boolean }>(), {
  disabled: false,
})

const { path } = toRefs(props)

const { url } = useRouter()

const crumbs = computed(() => {
  const all = mediaBrowser.generateBreadcrumbs(path.value)

  if (all.length <= 3) {
    return all
  }

  // truncate the middle part of the path
  const start = all.slice(0, 1)
  const end = all.slice(-2)

  return [...start, { name: '…', path: null }, ...end]
})
</script>

<style scoped lang="postcss">
.disabled {
  @apply opacity-50 cursor-not-allowed pointer-events-none;
}

li:not(:first-of-type)::before {
  content: '/';
  @apply font-normal opacity-50 inline-block mx-1.5;
}

li:last-of-type a {
  @apply font-semibold text-k-text-primary cursor-default;
}
</style>
