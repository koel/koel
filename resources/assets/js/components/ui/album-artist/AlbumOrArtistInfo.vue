<template>
  <article :class="mode" class="overflow-hidden">
    <h3 v-if="mode === 'aside'" class="text-3xl font-thin gap-3 mb-8">
      <slot name="header" />
    </h3>
    <div>
      <article v-if="mode === 'aside'" class="mb-5">
        <slot name="art" />
      </article>

      <main>
        <slot />
      </main>

      <footer v-if="$slots.footer" class="mt-6 text-right text-[0.95rem]">
        <slot name="footer" />
      </footer>
    </div>
  </article>
</template>

<script setup lang="ts">
import { toRefs } from 'vue'

const props = withDefaults(defineProps<{ mode?: MediaInfoDisplayMode }>(), { mode: 'aside' })
const { mode } = toRefs(props)
</script>
