<template>
  <Btn
    v-koel-tooltip
    :aria-pressed="active"
    class="border border-k-fg-10"
    variant="ghost"
    @click.prevent="emit('toggle')"
  >
    <Icon :class="active && 'text-k-love'" :icon="active ? faHeart : faEmptyHeart" />
    <span class="sr-only">Favorites only</span>
  </Btn>
</template>

<script lang="ts" setup>
import { faHeart as faEmptyHeart } from '@fortawesome/free-regular-svg-icons'
import { faHeart } from '@fortawesome/free-solid-svg-icons'

import Btn from '@/components/ui/form/Btn.vue'

defineProps<{ active: boolean }>()
const emit = defineEmits<{ (e: 'toggle'): void }>()
</script>
