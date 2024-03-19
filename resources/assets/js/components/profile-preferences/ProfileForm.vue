<template>
  <form data-testid="update-profile-form" @submit.prevent="update">
    <div class="profile form-row">
      <div class="left">
        <div class="form-row">
          <label>
            Current Password
            <input
              v-model="profile.current_password"
              v-koel-focus
              name="current_password"
              placeholder="Required to update your profile"
              required
              type="password"
              data-testid="currentPassword"
            >
          </label>
        </div>

        <div class="form-row">
          <label>
            Name
            <input id="inputProfileName" v-model="profile.name" name="name" required type="text" data-testid="name">
          </label>
        </div>

        <div class="form-row">
          <label>
            Email Address
            <input
              id="inputProfileEmail" v-model="profile.email" name="email" required type="email"
              data-testid="email"
            >
          </label>
        </div>

        <div class="form-row">
          <label>
            New Password
            <PasswordField
              v-model="profile.new_password"
              autocomplete="new-password"
              data-testid="newPassword"
              minlength="10"
              placeholder="Leave empty to keep current password"
            />
            <span class="password-rules help">
              Min. 10 characters. Should be a mix of characters, numbers, and symbols.
            </span>
          </label>
        </div>
      </div>

      <EditableProfileAvatar :profile="profile" />
    </div>

    <div class="form-row">
      <Btn class="btn-submit" type="submit">Save</Btn>
      <span v-if="isDemo" class="demo-notice">
        Changes will not be saved in the demo version.
      </span>
    </div>
  </form>
</template>

<script lang="ts" setup>
import { onMounted, ref } from 'vue'
import { userStore } from '@/stores'
import { authService, UpdateCurrentProfileData } from '@/services'
import { logger, parseValidationError } from '@/utils'
import { useDialogBox, useMessageToaster } from '@/composables'

import Btn from '@/components/ui/Btn.vue'
import PasswordField from '@/components/ui/PasswordField.vue'
import EditableProfileAvatar from '@/components/profile-preferences/EditableProfileAvatar.vue'

const { toastSuccess } = useMessageToaster()
const { showErrorDialog } = useDialogBox()

const profile = ref<UpdateCurrentProfileData>({} as UpdateCurrentProfileData)

const isDemo = window.IS_DEMO

onMounted(() => {
  profile.value = {
    name: userStore.current.name,
    email: userStore.current.email,
    avatar: userStore.current.avatar,
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

<style lang="scss" scoped>
form {
  width: 66%;

  input {
    width: 100%;
  }

  .profile {
    display: flex;
    align-items: flex-start;
    gap: 2.5rem;

    .left {
      width: 50%;
    }
  }
}

.password-rules {
  display: block;
  margin-top: .75rem;
}

.demo-notice {
  font-size: .95rem;
  opacity: .7;
  margin-left: 5px;
}
</style>
