<template>
  <section>
    <h3 class="text-2xl mb-2">Two-Factor Authentication</h3>

    <p>
      Add an extra layer of security to your account. When enabled, you'll need a code from your authenticator app (or a
      recovery code) in addition to your password to log in.
    </p>

    <div class="mt-4">
      <Btn
        v-if="stage === 'idle' && !currentUser.two_factor"
        :disabled="submitting"
        type="button"
        @click.prevent="startEnrollment"
      >
        Enable Two-Factor Authentication
      </Btn>

      <TwoFactorManageActions
        v-else-if="stage === 'idle' && currentUser.two_factor"
        ref="manageActions"
        :submitting="submitting"
        @disable="onDisable"
        @regenerate="onRegenerate"
      />

      <TwoFactorEnrollment
        v-else-if="stage === 'enrolling'"
        :provisioning-uri="provisioningUri"
        :submitting="submitting"
        @cancel="resetState"
        @submit="onConfirm"
      />

      <TwoFactorRecoveryCodes v-else-if="stage === 'showing-codes'" :codes="recoveryCodes" @dismiss="resetState" />
    </div>
  </section>
</template>

<script lang="ts" setup>
import { ref, useTemplateRef } from 'vue'
import { authService } from '@/services/authService'
import { useAuthorization } from '@/composables/useAuthorization'
import { useDialogBox } from '@/composables/useDialogBox'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { userStore } from '@/stores/userStore'

import Btn from '@/components/ui/form/Btn.vue'
import TwoFactorEnrollment from '@/components/profile-preferences/TwoFactorEnrollment.vue'
import TwoFactorManageActions from '@/components/profile-preferences/TwoFactorManageActions.vue'
import TwoFactorRecoveryCodes from '@/components/profile-preferences/TwoFactorRecoveryCodes.vue'

type Stage = 'idle' | 'enrolling' | 'showing-codes'

const { currentUser } = useAuthorization()
const { toastSuccess, toastError } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()

const stage = ref<Stage>('idle')
const provisioningUri = ref('')
const recoveryCodes = ref<string[]>([])
const submitting = ref(false)
const manageActions = useTemplateRef<InstanceType<typeof TwoFactorManageActions>>('manageActions')

const resetState = () => {
  stage.value = 'idle'
  provisioningUri.value = ''
  recoveryCodes.value = []
}

const startEnrollment = async () => {
  submitting.value = true

  try {
    const { provisioning_uri } = await authService.enrollTwoFactor()
    provisioningUri.value = provisioning_uri
    stage.value = 'enrolling'
  } catch {
    toastError('Failed to start two-factor enrollment.')
  } finally {
    submitting.value = false
  }
}

const onConfirm = async (code: string) => {
  submitting.value = true

  try {
    const { recovery_codes } = await authService.confirmTwoFactor(code)
    recoveryCodes.value = recovery_codes
    userStore.state.current.two_factor = true
    stage.value = 'showing-codes'
    toastSuccess('Two-factor authentication enabled.')
  } catch {
    toastError('Invalid code.')
  } finally {
    submitting.value = false
  }
}

const onRegenerate = async (code: string) => {
  submitting.value = true

  try {
    const { recovery_codes } = await authService.regenerateRecoveryCodes(code)
    recoveryCodes.value = recovery_codes
    stage.value = 'showing-codes'
    manageActions.value?.reset()
    toastSuccess('Recovery codes regenerated.')
  } catch {
    toastError('Invalid code.')
  } finally {
    submitting.value = false
  }
}

const onDisable = async (code: string) => {
  if (!(await showConfirmDialog('Disable two-factor authentication?'))) {
    return
  }

  submitting.value = true

  try {
    await authService.disableTwoFactor(code)
    userStore.state.current.two_factor = false
    manageActions.value?.reset()
    toastSuccess('Two-factor authentication disabled.')
  } catch {
    toastError('Invalid code.')
  } finally {
    submitting.value = false
  }
}
</script>
