<template>
  <img
    :alt="`Avatar of ${user.name}`"
    :src="avatar"
    :title="user.name"
    class="object-cover rounded-full aspect-square bg-k-bg-primary"
    @error="avatar = defaultCover"
  >
</template>

<script lang="ts" setup>
import { ref, toRefs } from 'vue'
import { defaultCover } from '@/utils'

const props = defineProps<{ user: Pick<User, 'name' | 'avatar'> }>()
const { user } = toRefs(props)
const avatar = ref(user.value.avatar)
</script>
