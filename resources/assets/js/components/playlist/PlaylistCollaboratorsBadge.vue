<template>
  <div>
    <ul>
      <li v-for="user in displayedCollaborators" :key="user.id">
        <UserAvatar :user="user" width="24" />
      </li>
    </ul>
    <span v-if="remainderCount" class="more">
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

<style scoped lang="postcss">
div {
  display: inline-block;
  vertical-align: middle;
}

ul {
  display: inline-block;
  vertical-align: middle;
}

li {
  display: inline-block;
  vertical-align: middle;
}

li + li {
  margin-left: -.3rem;
}

.more {
  margin-left: .3rem;
}
</style>
