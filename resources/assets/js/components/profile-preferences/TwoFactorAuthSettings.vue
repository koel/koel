<template>
  <section>
    <h3 class="text-2xl mb-2">Two-Factor Authentication</h3>

    <p>
      Add an extra layer of security to your account. When enabled, you'll need a code from your authenticator app (or a
      recovery code) in addition to your password to log in.
    </p>

    <div class="mt-4">
      <Btn v-if="stage === 'idle' && !currentUser.two_factor" type="button" @click.prevent="stage = 'enrolling'">
        Enable Two-Factor Authentication
      </Btn>

      <TwoFactorManageActions
        v-else-if="stage === 'idle' && currentUser.two_factor"
        @disabled="onDisabled"
        @regenerated="onShowCodes"
      />

      <TwoFactorEnrollment v-else-if="stage === 'enrolling'" @cancel="stage = 'idle'" @enrolled="onEnrolled" />

      <TwoFactorRecoveryCodes
        v-else-if="stage === 'showing-codes'"
        :codes="recoveryCodes"
        @dismiss="finishShowingCodes"
      />
    </div>
  </section>
</template>

<script lang="ts" setup>
import { ref } from 'vue'
import { useAuthorization } from '@/composables/useAuthorization'
import { userStore } from '@/stores/userStore'

import Btn from '@/components/ui/form/Btn.vue'
import TwoFactorEnrollment from '@/components/profile-preferences/TwoFactorEnrollment.vue'
import TwoFactorManageActions from '@/components/profile-preferences/TwoFactorManageActions.vue'
import TwoFactorRecoveryCodes from '@/components/profile-preferences/TwoFactorRecoveryCodes.vue'

type Stage = 'idle' | 'enrolling' | 'showing-codes'

const { currentUser } = useAuthorization()

const stage = ref<Stage>('idle')
const recoveryCodes = ref<string[]>([])

const onShowCodes = (codes: string[]) => {
  recoveryCodes.value = codes
  stage.value = 'showing-codes'
}

const onEnrolled = (codes: string[]) => {
  userStore.state.current.two_factor = true
  onShowCodes(codes)
}

const onDisabled = () => {
  userStore.state.current.two_factor = false
}

const finishShowingCodes = () => {
  recoveryCodes.value = []
  stage.value = 'idle'
}
</script>
