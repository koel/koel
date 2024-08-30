<template>
  <img
    :alt="`Avatar of ${user.name}`"
    :src="avatar"
    :title="user.name"
    @error="avatar = defaultCover"
    class="object-cover rounded-full aspect-square bg-k-bg-primary"
  >
</template>

<script lang="ts" setup>
import { toRefs, ref } from 'vue'
import { defaultCover } from '@/utils'

const props = defineProps<{ user: Pick<User, 'name' | 'avatar'> }>()
const { user } = toRefs(props)
const avatar = ref(user.value.avatar)
</script>
