<template>
  <article>
    <div :class="showingFull ? 'line-clamp-none' : 'line-clamp-[10]'">
      <slot />
    </div>

    <Btn v-if="!showingFull" class="mt-4" small @click.prevent="showingFull = true">
      Read More
    </Btn>
  </article>
</template>

<script lang="ts" setup>
import { ref } from 'vue'

import Btn from '@/components/ui/form/Btn.vue'

const showingFull = ref(false)
</script>
