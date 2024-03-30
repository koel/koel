<template>
  <form data-testid="update-profile-form" @submit.prevent="update">
    <AlertBox v-if="currentUser.sso_provider">
      You’re logging in via Single Sign On provided by <strong>{{ currentUser.sso_provider }}</strong>.
      You can still update your name and avatar here.
    </AlertBox>
    <div class="profile form-row">
      <div class="left">
        <div v-if="!currentUser.sso_provider" class="form-row">
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
            <input id="inputProfileName" v-model="profile.name" data-testid="name" name="name" required type="text">
          </label>
        </div>

        <div class="form-row">
          <label>
            Email Address
            <input
              id="inputProfileEmail"
              v-model="profile.email"
              :readonly="currentUser.sso_provider"
              data-testid="email"
              name="email"
              required
              type="email"
            >
          </label>
        </div>

        <div v-if="!currentUser.sso_provider" class="form-row">
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
import { authService, UpdateCurrentProfileData } from '@/services'
import { logger, parseValidationError } from '@/utils'
import { useDialogBox, useMessageToaster, useAuthorization } from '@/composables'

import Btn from '@/components/ui/Btn.vue'
import PasswordField from '@/components/ui/PasswordField.vue'
import EditableProfileAvatar from '@/components/profile-preferences/EditableProfileAvatar.vue'
import AlertBox from '@/components/ui/AlertBox.vue'

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

<style lang="scss" scoped>
form {
  width: 66%;

  @media (max-width: 1024px) {
    width: 100%;
  }

  input {
    width: 100%;
  }

  .profile {
    display: flex;
    align-items: flex-start;
    gap: 2.5rem;

    @media (max-width: 1024px) {
      flex-direction: column;
    }

    .left {
      width: 50%;

      @media (max-width: 1024px) {
        width: 100%;
      }
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
