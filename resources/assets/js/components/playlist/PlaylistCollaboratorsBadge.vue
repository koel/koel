<template>
  <div class="inline-block align-middle">
    <ul class="align-middle -space-x-2">
      <li v-for="user in displayedCollaborators" :key="user.id" class="inline-block align-baseline">
        <UserAvatar :user="user" width="24" class="border border-white/30" />
      </li>
    </ul>
    <span v-if="remainderCount" class="ml-2">
      +{{ remainderCount }} more
    </span>
  </div>
</template>

<script setup lang="ts">
import { computed, toRefs } from 'vue'

import UserAvatar from '@/components/user/UserAvatar.vue'

const props = defineProps<{ collaborators: PlaylistCollaborator[] }>()
const { collaborators } = toRefs(props)

const displayedCollaborators = computed(() => collaborators.value.slice(0, 3))
const remainderCount = computed(() => collaborators.value.length - displayedCollaborators.value.length)
</script>
