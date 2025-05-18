<template>
  <ul class="text-base">
    <li v-for="path in paths" :key="path.path" class="inline-block">
      <a :href="url('media-browser', { path: path.path })" class="text-k-text-secondary font-normal">
        {{ path.name }}
      </a>
    </li>
  </ul>
</template>

<script setup lang="ts">
import { useRouter } from '@/composables/useRouter'
import { computed, toRefs } from 'vue'
import { mediaBrowser } from '@/services/mediaBrowser'

const props = defineProps<{ path: string }>()
const { path } = toRefs(props)

const { url } = useRouter()

const paths = computed(() => mediaBrowser.getBreadcrumbs(path.value))
</script>

<style scoped lang="postcss">
li:not(:first-of-type)::before {
  content: '/';
  @apply font-normal opacity-50 inline-block mx-1.5;
}

li:last-of-type a {
  @apply font-semibold text-k-text-primary cursor-default;
}
</style>
