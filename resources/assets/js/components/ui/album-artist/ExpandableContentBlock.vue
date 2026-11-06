<template>
  <article>
    <div :class="showingFull ? '' : 'max-h-[3lh] overflow-hidden'">
      <slot />
    </div>

    <LinkButton v-if="!showingFull" class="mt-2" variant="highlight" @click.prevent="showingFull = true"
      >More</LinkButton
    >
  </article>
</template>

<script lang="ts" setup>
import { ref } from 'vue'

import LinkButton from '@/components/ui/form/LinkButton.vue'

const showingFull = ref(false)
</script>
