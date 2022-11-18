<template>
  <article :class="{ me: isCurrentUser }" class="user-card" data-testid="user-card">
    <img :alt="`${user.name}'s avatar`" :src="user.avatar" height="80" width="80">

    <main>
      <h1>
        <span class="name">{{ user.name }}</span>
        <icon v-if="isCurrentUser" :icon="faCircleCheck" class="you text-highlight" title="This is you!"/>
        <icon
          v-if="user.is_admin"
          :icon="faShield"
          class="is-admin text-blue"
          title="User has admin privileges"
        />
      </h1>

      <p class="email text-secondary">{{ user.email }}</p>

      <footer>
        <Btn class="btn-edit" data-testid="edit-user-btn" orange small @click="edit">
          {{ isCurrentUser ? 'Your Profile' : 'Edit' }}
        </Btn>
        <Btn v-if="!isCurrentUser" class="btn-delete" data-testid="delete-user-btn" red small @click="confirmDelete">
          Delete
        </Btn>
      </footer>
    </main>
  </article>
</template>

<script lang="ts" setup>
import { faCircleCheck, faShield } from '@fortawesome/free-solid-svg-icons'
import { computed, toRefs } from 'vue'
import { userStore } from '@/stores'
import { eventBus, requireInjection } from '@/utils'
import { useAuthorization, useDialogBox, useMessageToaster } from '@/composables'
import { RouterKey } from '@/symbols'

import Btn from '@/components/ui/Btn.vue'

const props = defineProps<{ user: User }>()
const { user } = toRefs(props)

const { toastSuccess } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()
const router = requireInjection(RouterKey)

const { currentUser } = useAuthorization()

const isCurrentUser = computed(() => user.value.id === currentUser.value.id)

const edit = () => isCurrentUser.value ? router.go('profile') : eventBus.emit('MODAL_SHOW_EDIT_USER_FORM', user.value)

const confirmDelete = async () =>
  await showConfirmDialog(`You’re about to unperson ${user.value.name}. Are you sure?`) && await destroy()

const destroy = async () => {
  await userStore.destroy(user.value)
  toastSuccess(`User "${user.value.name}" deleted.`)
}
</script>

<style lang="scss" scoped>
.user-card {
  padding: 10px;
  display: flex;
  flex-direction: row;
  align-items: center;
  border-radius: 5px;
  background: var(--color-bg-secondary);
  border: 1px solid var(--color-bg-secondary);
  gap: 1rem;

  img {
    border-radius: 50%;
    flex: 0 0 80px;
    background: rgba(0, 0, 0, .2)
  }

  main {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    position: relative;
    gap: .5rem;
  }

  h1 {
    font-size: 1rem;
    font-weight: var(--font-weight-normal);

    > * + * {
      margin-left: .5rem
    }
  }

  footer {
    visibility: hidden;

    > * + * {
      margin-left: .3rem;
    }

    @media (hover: none) {
      visibility: visible;
    }
  }

  &:hover footer {
    visibility: visible;
  }
}
</style>
