<template>
  <SettingGroup>
    <template #title>Two-Factor Authentication</template>

    <template v-if="stage === 'idle' && !currentUser.two_factor">
      <p class="mb-4">
        Two-factor authentication adds an extra layer of security to your account by requiring a second form of
        verification when you log in. You can use an authenticator app like Google Authenticator or Authy to generate
        time-based one-time passwords.
      </p>
      <Btn type="button" @click.prevent="stage = 'enrolling'"> Enable Two-Factor Authentication </Btn>
    </template>

    <TwoFactorManageActions
      v-else-if="stage === 'idle' && currentUser.two_factor"
      @disabled="onDisabled"
      @regenerated="showRecoveryCodes"
    />

    <TwoFactorEnrollment v-else-if="stage === 'enrolling'" @cancel="stage = 'idle'" @enrolled="onEnrolled" />

    <TwoFactorRecoveryCodes
      v-else-if="stage === 'showing-codes'"
      :codes="recoveryCodes"
      @dismiss="finishShowingCodes"
    />
  </SettingGroup>
</template>

<script lang="ts" setup>
import { ref } from 'vue'
import { useAuthorization } from '@/composables/useAuthorization'
import { userStore } from '@/stores/userStore'

import Btn from '@/components/ui/form/Btn.vue'
import SettingGroup from '@/components/screens/settings/SettingGroup.vue'
import TwoFactorEnrollment from '@/components/auth/two-factor/TwoFactorEnrollment.vue'
import TwoFactorManageActions from '@/components/auth/two-factor/TwoFactorManageActions.vue'
import TwoFactorRecoveryCodes from '@/components/auth/two-factor/TwoFactorRecoveryCodes.vue'

type Stage = 'idle' | 'enrolling' | 'showing-codes'

const { currentUser } = useAuthorization()

const stage = ref<Stage>('idle')
const recoveryCodes = ref<string[]>([])

const showRecoveryCodes = (codes: string[]) => {
  recoveryCodes.value = codes
  stage.value = 'showing-codes'
}

const onEnrolled = (codes: string[]) => {
  userStore.state.current.two_factor = true
  showRecoveryCodes(codes)
}

const onDisabled = () => {
  userStore.state.current.two_factor = false
}

const finishShowingCodes = () => {
  recoveryCodes.value = []
  stage.value = 'idle'
}
</script>
