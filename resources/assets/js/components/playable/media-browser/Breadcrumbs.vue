<template>
  <ul class="text-base" :class="disabled && 'disabled'">
    <li v-for="(crumb, idx) in crumbs" :key="String(crumb.id ?? `ellipsis-${idx}`)" class="inline-block">
      <a v-if="crumb.kind === 'root'" :href="url('media-browser')" class="text-k-fg-70 font-normal">Library</a>
      <a
        v-else-if="crumb.kind === 'ancestor'"
        :href="url('media-browser', { folder: crumb.id })"
        class="text-k-fg-70 font-normal"
      >
        {{ crumb.name }}
      </a>
      <span v-else>{{ crumb.name }}</span>
    </li>
  </ul>
</template>

<script setup lang="ts">
import { computed, toRefs } from 'vue'
import { useRouter } from '@/composables/useRouter'

type Crumb =
  | { kind: 'root'; id: null; name: 'Library' }
  | { kind: 'ancestor'; id: string; name: string }
  | { kind: 'current' | 'ellipsis'; id: null; name: string }

const props = withDefaults(defineProps<{ current: Folder | null; ancestors: Folder[]; disabled?: boolean }>(), {
  disabled: false,
})

const { current, ancestors } = toRefs(props)
const { url } = useRouter()

const crumbs = computed<Crumb[]>(() => {
  const full: Crumb[] = [
    { kind: 'root', id: null, name: 'Library' },
    ...ancestors.value.map<Crumb>(a => ({ kind: 'ancestor', id: a.id, name: a.name })),
    ...(current.value ? [{ kind: 'current', id: null, name: current.value.name } as Crumb] : []),
  ]

  if (full.length <= 4) {
    return full
  }

  // Truncate middle: keep "Library > … > [direct parent] > [current]"
  return [full[0], { kind: 'ellipsis', id: null, name: '…' }, ...full.slice(-2)]
})
</script>

<style scoped lang="postcss">
@reference '@css/app.pcss';
.disabled {
  @apply opacity-50 cursor-not-allowed pointer-events-none;
}

li:not(:first-of-type)::before {
  content: '/';
  @apply font-normal opacity-50 inline-block mx-1.5;
}

li:last-of-type span {
  @apply font-semibold text-k-fg cursor-default;
}
</style>
