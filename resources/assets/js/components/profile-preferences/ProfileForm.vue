<template>
  <form data-testid="update-profile-form" @submit.prevent="handleSubmit">
    <AlertBox v-if="currentUser.sso_provider">
      <template v-if="currentUser.sso_provider === 'Reverse Proxy'">
        You’re authenticated by a reverse proxy.
      </template>
      <template v-else>
        You’re logged in via single sign-on provided by <strong>{{ currentUser.sso_provider }}</strong
        >.
      </template>
      You can still update your name and avatar here.
    </AlertBox>

    <div class="flex flex-col gap-3 md:flex-row md:gap-8 w-full md:w-[640px]">
      <div class="flex-1 space-y-5">
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
        </FormRow>
      </div>

      <div>
        <EditableProfileAvatar :profile="data" @changed="onAvatarChanged" />
      </div>
    </div>

    <footer class="mt-8">
      <Btn class="btn-submit" type="submit">Save</Btn>
      <span v-if="isDemo" class="text-[.95rem] opacity-70 ml-2">Changes will not be saved in the demo version.</span>
    </footer>
  </form>
</template>

<script lang="ts" setup>
import { pick } from 'lodash-es'
import type { UpdateCurrentProfileData } from '@/services/authService'
import { authService } from '@/services/authService'
import { useAuthorization } from '@/composables/useAuthorization'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useForm } from '@/composables/useForm'

import Btn from '@/components/ui/form/Btn.vue'
import EditableProfileAvatar from '@/components/profile-preferences/EditableProfileAvatar.vue'
import AlertBox from '@/components/ui/AlertBox.vue'
import TextInput from '@/components/ui/form/TextInput.vue'
import FormRow from '@/components/ui/form/FormRow.vue'

const { toastSuccess } = useMessageToaster()
const { currentUser } = useAuthorization()

const isDemo = window.KOEL.is_demo

const { data, handleSubmit } = useForm<UpdateCurrentProfileData>({
  initialValues: {
    ...pick(currentUser.value, 'name', 'email', 'avatar'),
  },
  onSubmit: async data => {
    if (isDemo) {
      return
    }

    await authService.updateProfile(data)
  },
  onSuccess: () => toastSuccess('Profile updated.'),
})

const onAvatarChanged = (avatar: string) => {
  data.avatar = avatar
}
</script>
