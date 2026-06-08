<template>
  <section>
    <h3 class="text-2xl mb-2">Two-Factor Authentication</h3>

    <p>
      Add an extra layer of security to your account. When enabled, you'll need a code from your authenticator app (or a
      recovery code) in addition to your password to log in.
    </p>

    <div v-if="stage === 'idle' && !currentUser.two_factor" class="mt-4">
      <Btn :disabled="submitting" type="button" @click.prevent="startSetup"> Enable Two-Factor Authentication </Btn>
    </div>

    <div v-else-if="stage === 'idle' && currentUser.two_factor" class="mt-4 space-y-4">
      <p class="text-k-success">Two-factor authentication is active on your account.</p>

      <form v-if="manageAction" class="space-y-3 max-w-md" @submit.prevent="submitManage">
        <FormRow>
          <template #label>
            Enter a code from your authenticator app or a recovery code to
            {{ manageAction === 'disable' ? 'disable' : 'regenerate recovery codes' }}.
          </template>
          <TextInput v-model="manageCode" v-koel-focus autocomplete="one-time-code" placeholder="123 456" required />
        </FormRow>
        <div class="flex gap-2">
          <Btn :disabled="submitting" type="submit">Submit</Btn>
          <Btn type="button" variant="outline" @click.prevent="cancelManage">Cancel</Btn>
        </div>
      </form>

      <div v-else class="flex gap-2">
        <Btn type="button" @click.prevent="startManage('regenerate')">Regenerate Recovery Codes</Btn>
        <Btn type="button" variant="destructive" @click.prevent="startManage('disable')">Disable</Btn>
      </div>
    </div>

    <div v-else-if="stage === 'enrolling'" class="mt-4 space-y-4">
      <p>Scan this QR code with your authenticator app (1Password, Authy, Google Authenticator, etc.).</p>

      <div class="block w-fit rounded-md overflow-hidden bg-white p-2">
        <img :src="qrCodeUrl" alt="Two-factor authentication QR code" height="192" width="192" />
      </div>

      <form class="space-y-3 max-w-md" @submit.prevent="confirm">
        <FormRow>
          <template #label>Enter the 6-digit code shown in your authenticator app.</template>
          <TextInput v-model="enrollCode" v-koel-focus autocomplete="one-time-code" placeholder="123 456" required />
        </FormRow>
        <div class="flex gap-2">
          <Btn :disabled="submitting" type="submit">Confirm</Btn>
          <Btn type="button" variant="outline" @click.prevent="cancelSetup">Cancel</Btn>
        </div>
      </form>
    </div>

    <div v-else-if="stage === 'showing-codes'" class="mt-4 space-y-4">
      <AlertBox>
        Save these recovery codes somewhere safe. Each can be used once if you lose access to your authenticator app —
        you won't be able to see them again.
      </AlertBox>

      <ul class="font-mono text-sm rounded-md border border-k-fg-10 bg-k-bg-50 p-4 grid grid-cols-2 gap-2">
        <li v-for="code in recoveryCodes" :key="code">{{ code }}</li>
      </ul>

      <div class="flex gap-2">
        <Btn type="button" @click.prevent="copyCodes"> <CopyIcon :size="16" /> Copy </Btn>
        <Btn type="button" variant="outline" @click.prevent="finishShowingCodes">I've saved them</Btn>
      </div>
    </div>
  </section>
</template>

<script lang="ts" setup>
import { CopyIcon } from 'lucide-vue-next'
import { ref } from 'vue'
import { useQRCode } from '@vueuse/integrations/useQRCode'
import { authService } from '@/services/authService'
import { useAuthorization } from '@/composables/useAuthorization'
import { useDialogBox } from '@/composables/useDialogBox'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { userStore } from '@/stores/userStore'
import { copyText } from '@/utils/helpers'

import AlertBox from '@/components/ui/AlertBox.vue'
import Btn from '@/components/ui/form/Btn.vue'
import FormRow from '@/components/ui/form/FormRow.vue'
import TextInput from '@/components/ui/form/TextInput.vue'

type Stage = 'idle' | 'enrolling' | 'showing-codes'
type ManageAction = 'regenerate' | 'disable' | null

const { currentUser } = useAuthorization()
const { toastSuccess, toastError } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()

const stage = ref<Stage>('idle')
const manageAction = ref<ManageAction>(null)
const provisioningUri = ref('')
const enrollCode = ref('')
const manageCode = ref('')
const recoveryCodes = ref<string[]>([])
const submitting = ref(false)

const qrCodeUrl = useQRCode(provisioningUri, {
  width: window.devicePixelRatio === 1 ? 192 : 384,
  height: window.devicePixelRatio === 1 ? 192 : 384,
})

const startSetup = async () => {
  submitting.value = true

  try {
    const { provisioning_uri } = await authService.setupTwoFactor()
    provisioningUri.value = provisioning_uri
    stage.value = 'enrolling'
  } catch {
    toastError('Failed to start two-factor setup.')
  } finally {
    submitting.value = false
  }
}

const cancelSetup = () => {
  stage.value = 'idle'
  provisioningUri.value = ''
  enrollCode.value = ''
}

const confirm = async () => {
  submitting.value = true

  try {
    const { recovery_codes } = await authService.confirmTwoFactor(enrollCode.value)
    recoveryCodes.value = recovery_codes
    userStore.state.current.two_factor = true
    stage.value = 'showing-codes'
    enrollCode.value = ''
    toastSuccess('Two-factor authentication enabled.')
  } catch {
    toastError('Invalid code.')
  } finally {
    submitting.value = false
  }
}

const finishShowingCodes = () => {
  stage.value = 'idle'
  recoveryCodes.value = []
  provisioningUri.value = ''
}

const startManage = (action: 'regenerate' | 'disable') => {
  manageAction.value = action
  manageCode.value = ''
}

const cancelManage = () => {
  manageAction.value = null
  manageCode.value = ''
}

const submitManage = async () => {
  if (!manageAction.value) {
    return
  }

  if (manageAction.value === 'disable' && !(await showConfirmDialog('Disable two-factor authentication?'))) {
    return
  }

  submitting.value = true

  try {
    if (manageAction.value === 'regenerate') {
      const { recovery_codes } = await authService.regenerateRecoveryCodes(manageCode.value)
      recoveryCodes.value = recovery_codes
      stage.value = 'showing-codes'
      toastSuccess('Recovery codes regenerated.')
    } else {
      await authService.disableTwoFactor(manageCode.value)
      userStore.state.current.two_factor = false
      toastSuccess('Two-factor authentication disabled.')
    }
    manageAction.value = null
    manageCode.value = ''
  } catch {
    toastError('Invalid code.')
  } finally {
    submitting.value = false
  }
}

const copyCodes = async () => {
  await copyText(recoveryCodes.value.join('\n'))
  toastSuccess('Recovery codes copied.')
}
</script>
