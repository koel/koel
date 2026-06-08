<template>
  <section>
    <h3 class="text-2xl mb-2">Change Password</h3>

    <AlertBox v-if="currentUser.sso_provider">
      Your password is managed by your single sign-on provider ({{ currentUser.sso_provider }}). You can't change it
      here.
    </AlertBox>

    <form v-else class="space-y-5 max-w-md mt-4" data-testid="change-password-form" @submit.prevent="handleSubmit">
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

      <Btn type="submit">Update Password</Btn>
    </form>
  </section>
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

const { currentUser } = useAuthorization()
const { toastSuccess } = useMessageToaster()

const { data, handleSubmit } = useForm<{ current_password: string; new_password: string }>({
  initialValues: { current_password: '', new_password: '' },
  onSubmit: async ({ current_password, new_password }) => {
    await authService.changePassword(current_password, new_password)
  },
  onSuccess: () => {
    data.current_password = ''
    data.new_password = ''
    toastSuccess('Password updated.')
  },
})
</script>
