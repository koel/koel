<template>
  <article>
    <div :class="showingFull ? 'line-clamp-none' : 'line-clamp-[10]'">
      <slot />
    </div>

    <Btn v-if="!showingFull" small class="mt-4" @click.prevent="showingFull = true">
      Read More
    </Btn>
  </article>
</template>

<script setup lang="ts">
import { ref } from 'vue'

import Btn from '@/components/ui/form/Btn.vue'

const showingFull = ref(false)
</script>
