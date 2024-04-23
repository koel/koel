<template>
  <li
    class="flex items-center justify-center w-full gap-3 py-2 px-3 rounded-md transition-colors duration-200 ease-in-out
    bg-k-bg-secondary border border-k-border hover:border hover:border-white/15"
  >
    <span class="avatar">
      <UserAvatar :user="collaborator" width="32" />
    </span>
    <span class="flex-1">
      {{ collaborator.name }}
      <Icon
        v-if="collaborator.id === currentUser.id"
        :icon="faCircleCheck"
        class="text-k-highlight ml-1"
        title="This is you!"
      />
    </span>
    <span class="role text-k-text-secondary text-right flex-[0_0_104px] uppercase">
      <span v-if="role === 'owner'" class="owner">Owner</span>
      <span v-else class="contributor">Contributor</span>
    </span>
    <span v-if="manageable" class="actions flex-[0_0_72px] text-right">
      <Btn v-if="removable" danger small @click.prevent="emit('remove')">Remove</Btn>
    </span>
  </li>
</template>

<script lang="ts" setup>
import { faCircleCheck } from '@fortawesome/free-solid-svg-icons'
import { toRefs } from 'vue'

import Btn from '@/components/ui/form/Btn.vue'
import UserAvatar from '@/components/user/UserAvatar.vue'
import { useAuthorization } from '@/composables'

const props = defineProps<{
  collaborator: PlaylistCollaborator,
  removable: boolean,
  manageable: boolean,
  role: 'owner' | 'contributor'
}>()

const { collaborator, removable, role } = toRefs(props)
const { currentUser } = useAuthorization()

const emit = defineEmits<{ (e: 'remove'): void }>()
</script>

<style lang="postcss" scoped>
span {
  @apply inline-block min-w-0 leading-normal;
}

.role span {
  @apply px-2 py-1 rounded-md border border-white/20;
}

&:only-child .actions:not(:has(button)) {
  @apply hidden;
}
</style>
