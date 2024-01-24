<template>
  <article :class="{ me: isCurrentUser }" class="user-card">
    <UserAvatar :user="user" width="80" />

    <main>
      <h1>
        <span v-if="user.name" class="name">{{ user.name }}</span>
        <span v-else class="name anonymous">Anonymous</span>
        <Icon v-if="isCurrentUser" :icon="faCircleCheck" class="you text-highlight" title="This is you!" />
        <icon
          v-if="user.is_admin"
          :icon="faShield"
          class="is-admin text-blue"
          title="User has admin privileges"
        />
      </h1>

      <p class="email text-secondary">{{ user.email }}</p>

      <footer>
        <template v-if="user.is_prospect">
          <Btn class="btn-revoke" red small @click="revokeInvite">Revoke</Btn>
        </template>
        <template v-else>
          <Btn v-if="!isCurrentUser" class="btn-delete" red small @click="destroy">Delete</Btn>
          <Btn v-if="!user.is_prospect" class="btn-edit" orange small @click="edit">
            {{ isCurrentUser ? 'Your Profile' : 'Edit' }}
          </Btn>
        </template>
      </footer>
    </main>
  </article>
</template>

<script lang="ts" setup>
import { faCircleCheck, faShield } from '@fortawesome/free-solid-svg-icons'
import { computed, toRefs } from 'vue'
import { userStore } from '@/stores'
import { invitationService } from '@/services'
import { useAuthorization, useDialogBox, useMessageToaster, useRouter } from '@/composables'
import { eventBus, parseValidationError } from '@/utils'

import Btn from '@/components/ui/Btn.vue'
import UserAvatar from '@/components/user/UserAvatar.vue'

const props = defineProps<{ user: User }>()
const { user } = toRefs(props)

const { toastSuccess } = useMessageToaster()
const { showConfirmDialog, showErrorDialog } = useDialogBox()
const { go } = useRouter()

const { currentUser } = useAuthorization()

const isCurrentUser = computed(() => user.value.id === currentUser.value.id)

const edit = () => isCurrentUser.value ? go('profile') : eventBus.emit('MODAL_SHOW_EDIT_USER_FORM', user.value)

const destroy = async () => {
  if (!await showConfirmDialog(`Unperson ${user.value.name}?`)) return

  await userStore.destroy(user.value)
  toastSuccess(`User "${user.value.name}" deleted.`)
}

const revokeInvite = async () => {
  if (!await showConfirmDialog(`Revoke the invite for ${user.value.email}?`)) return

  try {
    await invitationService.revoke(user.value)
    toastSuccess(`Invitation for ${user.value.email} revoked.`)
  } catch (err: any) {
    if (err.response.status === 404) {
      showErrorDialog('Cannot revoke the invite. Maybe it has been accepted?', 'Revocation Failed')
      return
    }

    const msg = err.response.status === 422 ? parseValidationError(err.response.data)[0] : 'Unknown error.'
    showErrorDialog(msg, 'Error')
  }
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
  transition: border-color .2s ease-in-out;

  &:hover {
    border-color: rgba(255, 255, 255, .15);
  }

  .anonymous {
    font-weight: var(--font-weight-light);
    color: var(--color-text-secondary);
  }

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
