<template>
  <img
    :alt="`Avatar of ${user.name}`"
    :src="user.avatar"
    :title="user.name"
    class="rounded-full aspect-square bg-k-bg-primary object-cover"
  >
</template>

<script setup lang="ts">
import { toRefs } from 'vue'

const props = defineProps<{ user: Pick<User, 'name' | 'avatar'> }>()
const { user } = toRefs(props)
</script>
