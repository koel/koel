<template>
  <form data-testid="update-profile-form" @submit.prevent="update">
    <AlertBox v-if="currentUser.sso_provider">
      <template v-if="currentUser.sso_provider === 'Reverse Proxy'">
        You’re authenticated by a reverse proxy.
      </template>
      <template v-else>
        You’re logging in via single sign-on provided by <strong>{{ currentUser.sso_provider }}</strong>.
      </template>
      You can still update your name and avatar here.
    </AlertBox>

    <div class="flex flex-col gap-3 md:flex-row md:gap-8 w-full md:w-[640px]">
      <div class="flex-1 space-y-5">
        <FormRow v-if="!currentUser.sso_provider">
          <template #label>Current Password</template>
          <TextInput
            v-model="profile.current_password"
            v-koel-focus
            name="current_password"
            placeholder="Required to update your profile"
            required
            type="password"
            data-testid="currentPassword"
          />
        </FormRow>

        <FormRow>
          <template #label>Name</template>
          <TextInput v-model="profile.name" data-testid="name" name="name" />
        </FormRow>

        <FormRow>
          <template #label>Email Address</template>
          <TextInput
            id="inputProfileEmail"
            v-model="profile.email"
            :readonly="currentUser.sso_provider"
            data-testid="email"
            name="email"
            required
            type="email"
          />
        </FormRow>

        <FormRow v-if="!currentUser.sso_provider">
          <template #label>New Password</template>
          <PasswordField
            v-model="profile.new_password"
            autocomplete="new-password"
            data-testid="newPassword"
            minlength="10"
            placeholder="Leave empty to keep current password"
          />
          <template #help>Min. 10 characters. Should be a mix of characters, numbers, and symbols.</template>
        </FormRow>
      </div>

      <div>
        <EditableProfileAvatar :profile="profile" />
      </div>
    </div>

    <footer class="mt-8">
      <Btn class="btn-submit" type="submit">Save</Btn>
      <span v-if="isDemo" class="text-[.95rem] opacity-70 ml-2">
        Changes will not be saved in the demo version.
      </span>
    </footer>
  </form>
</template>

<script lang="ts" setup>
import { onMounted, ref } from 'vue'
import { authService, UpdateCurrentProfileData } from '@/services'
import { logger, parseValidationError } from '@/utils'
import { useDialogBox, useMessageToaster, useAuthorization } from '@/composables'

import Btn from '@/components/ui/form/Btn.vue'
import PasswordField from '@/components/ui/form/PasswordField.vue'
import EditableProfileAvatar from '@/components/profile-preferences/EditableProfileAvatar.vue'
import AlertBox from '@/components/ui/AlertBox.vue'
import TextInput from '@/components/ui/form/TextInput.vue'
import FormRow from '@/components/ui/form/FormRow.vue'

const { toastSuccess } = useMessageToaster()
const { showErrorDialog } = useDialogBox()

const profile = ref<UpdateCurrentProfileData>({} as UpdateCurrentProfileData)

const isDemo = window.IS_DEMO

const { currentUser } = useAuthorization()

onMounted(() => {
  profile.value = {
    name: currentUser.value.name,
    email: currentUser.value.email,
    avatar: currentUser.value.avatar,
    current_password: null
  }
})

const update = async () => {
  if (!profile.value) {
    throw Error()
  }

  if (isDemo) {
    toastSuccess('Profile updated.')
    return
  }

  try {
    await authService.updateProfile(Object.assign({}, profile.value))
    profile.value.current_password = null
    delete profile.value.new_password
    toastSuccess('Profile updated.')
  } catch (error: any) {
    const msg = error.response.status === 422 ? parseValidationError(error.response.data)[0] : 'Unknown error.'
    await showErrorDialog(msg, 'Error')
    logger.log(error)
  }
}
</script>
