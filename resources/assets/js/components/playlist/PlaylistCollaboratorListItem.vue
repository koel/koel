<template>
  <li>
    <span class="avatar">
      <UserAvatar :user="collaborator" width="32" />
    </span>
    <span class="name">
      {{ collaborator.name }}
      <Icon
        v-if="collaborator.id === currentUser.id"
        :icon="faCircleCheck"
        class="you text-highlight"
        title="This is you!"
      />
    </span>
    <span class="role text-secondary">
      <span v-if="role === 'owner'" class="owner">Owner</span>
      <span v-else class="contributor">Contributor</span>
    </span>
    <span v-if="manageable" class="actions">
      <Btn v-if="removable" small red @click.prevent="emit('remove')">Remove</Btn>
    </span>
  </li>
</template>

<script setup lang="ts">
import { faCircleCheck } from '@fortawesome/free-solid-svg-icons'
import { toRefs } from 'vue'

import Btn from '@/components/ui/Btn.vue'
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

<style scoped lang="postcss">
li {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  gap: 1rem;
  background: var(--color-bg-secondary);
  border: 1px solid var(--color-bg-secondary);
  padding: .5rem .8rem;
  border-radius: 5px;
  transition: border-color .2s ease-in-out;

  &:hover {
    border-color: rgba(255, 255, 255, .15);
  }

  .you {
    margin-left: .5rem;
  }

  span {
    display: inline-block;
    min-width: 0;
    line-height: 1;
  }

  .name {
    flex: 1;
  }

  .role {
    text-align: right;
    flex: 0 0 104px;
    text-transform: uppercase;

    span {
      padding: 3px 4px;
      border-radius: 4px;
      border: 1px solid rgba(255, 255, 255, .2);
    }
  }

  .actions {
    flex: 0 0 72px;
    text-align: right;
  }

  &:only-child {
    .actions:not(:has(button)) {
      display: none;
    }
  }
}
</style>
