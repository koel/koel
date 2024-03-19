<template>
  <img :alt="`Avatar of ${user.name}`" :src="user.avatar" :title="user.name">
</template>

<script setup lang="ts">
import { toRefs } from 'vue'

const props = defineProps<{ user: Pick<User, 'name' | 'avatar'> }>()
const { user } = toRefs(props)
</script>

<style scoped lang="scss">
img {
  border-radius: 50%;
  aspect-ratio: 1/1;
  background: var(--color-bg-primary);
  object-fit: cover;
}
</style>
