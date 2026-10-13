<template>
  <SettingGroup>
    <template #title>Other Sessions</template>
    <template #subtitle>Log out of every other browser and device.</template>

    <div>
      <Btn type="button" variant="destructive" @click.prevent="logOutOtherSessions">Log Out Other Sessions</Btn>
    </div>
  </SettingGroup>
</template>

<script lang="ts" setup>
import { authService } from '@/services/authService'
import { useDialogBox } from '@/composables/useDialogBox'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { useMessageToaster } from '@/composables/useMessageToaster'

import Btn from '@/components/ui/form/Btn.vue'
import SettingGroup from '@/components/screens/settings/SettingGroup.vue'

const { showConfirmDialog } = useDialogBox()
const { toastSuccess } = useMessageToaster()
const { handleHttpError } = useErrorHandler('dialog')

const logOutOtherSessions = async () => {
  if (!(await showConfirmDialog('Log out of all other sessions?'))) {
    return
  }

  try {
    await authService.logOutOtherSessions()
    toastSuccess('Logged out of other sessions.')
  } catch (error: unknown) {
    handleHttpError(error)
  }
}
</script>
