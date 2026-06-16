<template>
  <form @submit.prevent="handleSubmit">
    <SettingGroup>
      <template #title>Change Password</template>

      <AlertBox v-if="currentUser.sso_provider">
        Your password is managed by your single sign-on provider ({{ currentUser.sso_provider }}). You can't change it
        here.
      </AlertBox>

      <div v-else class="space-y-5 max-w-md">
        <FormRow>
          <template #label>Current Password</template>
          <PasswordField
            v-model="data.current_password"
            autocomplete="current-password"
            data-testid="current-password"
            required
          />
        </FormRow>

        <FormRow>
          <template #label>New Password</template>
          <PasswordField
            v-model="data.new_password"
            autocomplete="new-password"
            data-testid="new-password"
            minlength="10"
            required
          />
          <template #help>Min. 10 characters. Should be a mix of characters, numbers, and symbols.</template>
        </FormRow>
      </div>

      <template v-if="!currentUser.sso_provider" #footer>
        <Btn type="submit">Update Password</Btn>
        <span v-if="isDemo" class="text-[.95rem] opacity-70 ml-2">Changes will not be saved in the demo version.</span>
      </template>
    </SettingGroup>
  </form>
</template>

<script lang="ts" setup>
import { authService } from '@/services/authService'
import { useAuthorization } from '@/composables/useAuthorization'
import { useForm } from '@/composables/useForm'
import { useMessageToaster } from '@/composables/useMessageToaster'

import AlertBox from '@/components/ui/AlertBox.vue'
import Btn from '@/components/ui/form/Btn.vue'
import FormRow from '@/components/ui/form/FormRow.vue'
import PasswordField from '@/components/ui/form/PasswordField.vue'
import SettingGroup from '@/components/screens/settings/SettingGroup.vue'

const { currentUser } = useAuthorization()
const { toastSuccess } = useMessageToaster()

const isDemo = window.KOEL.is_demo

const { data, handleSubmit } = useForm<{ current_password: string; new_password: string }>({
  initialValues: { current_password: '', new_password: '' },
  onSubmit: async ({ current_password, new_password }) => {
    if (isDemo) {
      return
    }

    await authService.changePassword(current_password, new_password)
  },
  onSuccess: () => {
    data.current_password = ''
    data.new_password = ''
    toastSuccess('Password updated.')
  },
})
</script>
