<template>
  <img
    :alt="`Avatar of ${user.name}`"
    :src="user.avatar"
    :title="user.name"
    class="object-cover rounded-full aspect-square bg-k-bg-primary"
    @error="user.avatar = defaultCover"
  >
</template>

<script lang="ts" setup>
import { toRefs } from 'vue'
import { useBranding } from '@/composables/useBranding'

const props = defineProps<{ user: Pick<User, 'name' | 'avatar'> }>()
const { user } = toRefs(props)

const { cover: defaultCover } = useBranding()
</script>
