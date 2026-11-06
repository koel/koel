<template>
  <article>
    <div ref="content" :class="showingFull ? '' : 'max-h-[3lh] overflow-hidden'">
      <div ref="slotContent">
        <slot />
      </div>
    </div>

    <LinkButton v-if="overflowing && !showingFull" class="mt-2" variant="highlight" @click.prevent="showingFull = true"
      >More</LinkButton
    >
  </article>
</template>

<script lang="ts" setup>
import { useResizeObserver } from '@vueuse/core'
import { ref } from 'vue'

import LinkButton from '@/components/ui/form/LinkButton.vue'

const content = ref<HTMLElement>()
const slotContent = ref<HTMLElement>()
const showingFull = ref(false)
const overflowing = ref(false)

useResizeObserver([content, slotContent], () => {
  overflowing.value = content.value!.scrollHeight > content.value!.clientHeight + 1
})
</script>
