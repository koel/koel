<template>
  <img
    :alt="`Avatar of ${user.name}`"
    :src="user.avatar"
    :title="user.name"
    :onerror="user.avatar = defaultCover"
    class="rounded-full aspect-square bg-k-bg-primary object-cover"
  >
</template>

<script lang="ts" setup>
import { toRefs } from 'vue'
import { defaultCover } from '@/utils'

const props = defineProps<{ user: Pick<User, 'name' | 'avatar'> }>()
const { user } = toRefs(props)
</script>
