<template>
  <ContextMenu ref="base" data-testid="user-context-menu" extra-class="user-menu">
    <template v-if="user">
      <template v-if="allowEdit">
        <li @click="edit">Edit…</li>
      </template>
      <template v-if="allowDelete">
        <li v-if="user.is_prospect" @click="revokeInvite">Revoke Invitation</li>
        <li v-else @click="destroy">Delete</li>
      </template>
      <li
        v-if="allowEdit === false && allowDelete === false"
        class="italic pointer-events-none"
      >
        No available actions
      </li>
    </template>
  </ContextMenu>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { eventBus } from '@/utils/eventBus'
import { userStore } from '@/stores/userStore'
import { usePolicies } from '@/composables/usePolicies'
import { useContextMenu } from '@/composables/useContextMenu'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useDialogBox } from '@/composables/useDialogBox'
import { invitationService } from '@/services/invitationService'
import { useErrorHandler } from '@/composables/useErrorHandler'

const user = ref<User>()

const allowEdit = ref<boolean | null>(null)
const allowDelete = ref<boolean | null>(null)

const { toastSuccess } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()
const { base, ContextMenu, open, trigger } = useContextMenu()
const { currentUserCan } = usePolicies()

const edit = () => trigger(() => eventBus.emit('MODAL_SHOW_EDIT_USER_FORM', user.value!))

const destroy = () => trigger(async () => {
  if (!await showConfirmDialog(`Unperson ${user!.value!.name}?`)) {
    return
  }

  await userStore.destroy(user.value!)
  toastSuccess(`User "${user.value!.name}" deleted.`)
})

const revokeInvite = async () => {
  if (!await showConfirmDialog(`Revoke the invite for ${user.value!.email}?`)) {
    return
  }

  try {
    await invitationService.revoke(user.value!)
    toastSuccess(`Invitation for ${user.value!.email} revoked.`)
  } catch (error: unknown) {
    useErrorHandler('dialog').handleHttpError(error, {
      404: 'Cannot revoke the invite. Maybe it has been accepted?',
    })
  }
}

eventBus.on('USER_CONTEXT_MENU_REQUESTED', async ({ pageX, pageY }, _user) => {
  user.value = _user
  await open(pageY, pageX)

  allowEdit.value = user.value.is_prospect ? false : (await currentUserCan.editUser(user.value))
  allowDelete.value = await currentUserCan.deleteUser(user.value)
})
</script>
