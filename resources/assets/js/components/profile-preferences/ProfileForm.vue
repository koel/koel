<template>
  <form data-testid="update-profile-form" @submit.prevent="handleSubmit">
    <div class="flex flex-col gap-6 max-w-md">
      <EditableProfileAvatar v-model:avatar="data.avatar" :name="data.name" />

      <div class="space-y-5">
        <FormRow>
          <template #label>Name</template>
          <TextInput v-model="data.name" data-testid="name" name="name" />
        </FormRow>

        <FormRow>
          <template #label>Email Address</template>
          <TextInput
            id="inputProfileEmail"
            v-model="data.email"
            :readonly="currentUser.sso_provider"
            data-testid="email"
            name="email"
            required
            type="email"
          />
          <template v-if="currentUser.sso_provider" #help>
            <span data-testid="sso-email-help">
              Managed by
              {{ currentUser.sso_provider === 'Reverse Proxy' ? 'your reverse proxy' : currentUser.sso_provider }}.
            </span>
          </template>
        </FormRow>
      </div>
    </div>

    <footer class="mt-8">
      <Btn class="btn-submit" :disabled="loading" type="submit">Save</Btn>
      <span v-if="isDemo" class="text-[.95rem] opacity-70 ml-2">Changes will not be saved in the demo version.</span>
    </footer>
  </form>
</template>

<script lang="ts" setup>
import { pick } from 'lodash-es'
import type { UpdateCurrentProfileData } from '@/services/authService'
import { authService } from '@/services/authService'
import { useAuthorization } from '@/composables/useAuthorization'
import { useForm } from '@/composables/useForm'

import Btn from '@/components/ui/form/Btn.vue'
import EditableProfileAvatar from '@/components/profile-preferences/EditableProfileAvatar.vue'
import TextInput from '@/components/ui/form/TextInput.vue'
import FormRow from '@/components/ui/form/FormRow.vue'

const { currentUser } = useAuthorization()

const isDemo = window.KOEL.is_demo

const { data, loading, handleSubmit } = useForm<UpdateCurrentProfileData>({
  initialValues: {
    ...pick(currentUser.value, 'name', 'email'),
    avatar: undefined,
  },
  toast: {
    loading: 'Saving profile…',
    success: () => (data.email === currentUser.value.email ? 'Profile updated.' : 'Confirmation email sent.'),
  },
  onSubmit: async data => {
    if (isDemo) {
      return
    }

    await authService.updateProfile(data)
  },
  onSuccess: () => {
    data.avatar = undefined
    data.email = currentUser.value.email
  },
})
</script>
