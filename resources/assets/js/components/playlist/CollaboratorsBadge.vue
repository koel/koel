<template>
  <div>
    <ul>
      <li v-for="user in displayedCollaborators">
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

const props = defineProps<{ playlist: Playlist }>()
const { playlist } = toRefs(props)

const displayedCollaborators = computed(() => playlist.value.collaborators.slice(0, 3))
const remainderCount = computed(() => playlist.value.collaborators.length - displayedCollaborators.value.length)
</script>

<style scoped lang="scss">
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
