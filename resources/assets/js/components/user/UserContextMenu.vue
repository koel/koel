<template>
  <ul>
    <template v-if="allowEdit">
      <MenuItem @click="edit">{{ t('users.editAction') }}</MenuItem>
    </template>
    <template v-if="allowDelete">
      <MenuItem v-if="user.is_prospect" @click="revokeInvite">{{ t('users.revokeInvitation') }}</MenuItem>
      <MenuItem v-else @click="destroy">{{ t('users.deleteAction') }}</MenuItem>
    </template>
    <MenuItem
      v-if="allowEdit === false && allowDelete === false"
      class="italic pointer-events-none"
    >
      {{ t('users.noAvailableActions') }}
    </MenuItem>
  </ul>
</template>

<script setup lang="ts">
import { onMounted, ref, toRefs } from 'vue'
import { useI18n } from 'vue-i18n'
import { eventBus } from '@/utils/eventBus'
import { userStore } from '@/stores/userStore'
import { usePolicies } from '@/composables/usePolicies'
import { useContextMenu } from '@/composables/useContextMenu'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useDialogBox } from '@/composables/useDialogBox'
import { invitationService } from '@/services/invitationService'
import { useErrorHandler } from '@/composables/useErrorHandler'

const props = defineProps<{ user: User }>()
const { user } = toRefs(props)

const { t } = useI18n()
const allowEdit = ref<boolean | null>(null)
const allowDelete = ref<boolean | null>(null)

const { toastSuccess } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()
const { MenuItem, trigger } = useContextMenu()
const { currentUserCan } = usePolicies()

const edit = () => trigger(() => eventBus.emit('MODAL_SHOW_EDIT_USER_FORM', user.value))

const destroy = () => trigger(async () => {
  if (!await showConfirmDialog(t('users.deleteConfirm', { name: user!.value.name }))) {
    return
  }

  await userStore.destroy(user.value)
  toastSuccess(t('users.deleted', { name: user.value.name }))
})

const revokeInvite = async () => {
  if (!await showConfirmDialog(t('users.revokeInviteConfirm', { email: user.value.email }))) {
    return
  }

  try {
    await invitationService.revoke(user.value)
    toastSuccess(t('users.invitationRevoked', { email: user.value.email }))
  } catch (error: unknown) {
    useErrorHandler('dialog').handleHttpError(error, {
      404: t('users.cannotRevokeInvite'),
    })
  }
}

onMounted(async () => {
  allowEdit.value = user.value.is_prospect ? false : (await currentUserCan.editUser(user.value))
  allowDelete.value = await currentUserCan.deleteUser(user.value)
})
</script>
