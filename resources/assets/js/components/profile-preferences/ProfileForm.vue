<template>
  <form data-testid="update-profile-form" @submit.prevent="update">
    <div class="form-row">
      <label for="inputProfileCurrentPassword">Current Password</label>
      <input
        id="inputProfileCurrentPassword"
        v-model="profile.current_password"
        v-koel-focus
        name="current_password"
        placeholder="Required to update your profile"
        required
        type="password"
      >
    </div>

    <div class="form-row">
      <label for="inputProfileName">Name</label>
      <input id="inputProfileName" v-model="profile.name" name="name" required type="text">
    </div>

    <div class="form-row">
      <label for="inputProfileEmail">Email Address</label>
      <input id="inputProfileEmail" v-model="profile.email" name="email" required type="email">
    </div>

    <div class="change-password">
      <div class="form-row">
        <label for="inputProfileNewPassword">New Password</label>
        <input
          id="inputProfileNewPassword"
          v-model="profile.new_password"
          autocomplete="new-password"
          name="new_password"
          placeholder="Leave empty to keep current password"
          type="password"
        >
        <p class="password-rules help">
          Min. 10 characters. Should be a mix of characters, numbers, and symbols.<br>
        </p>
      </div>
    </div>

    <div class="form-row">
      <Btn class="btn-submit" type="submit">Save</Btn>
      <span v-if="demo" style="font-size:.95rem; opacity:.7; margin-left:5px">
        Changes will not be saved in the demo version.
      </span>
    </div>
  </form>
</template>

<script lang="ts" setup>
import { defineAsyncComponent, onMounted, ref } from 'vue'
import { UpdateCurrentProfileData, userStore } from '@/stores'
import { alerts, parseValidationError } from '@/utils'

const Btn = defineAsyncComponent(() => import('@/components/ui/Btn.vue'))

const demo = NODE_ENV === 'demo'
const profile = ref<UpdateCurrentProfileData>({} as unknown as UpdateCurrentProfileData)

onMounted(() => {
  profile.value = {
    name: userStore.current.name,
    email: userStore.current.email,
    current_password: null
  }
})

const update = async () => {
  if (!profile.value) {
    throw Error()
  }

  try {
    await userStore.updateProfile(profile.value)
    profile.value.current_password = null
    delete profile.value.new_password
    alerts.success('Profile updated.')
  } catch (err: any) {
    const msg = err.response.status === 422 ? parseValidationError(err.response.data)[0] : 'Unknown error.'
    alerts.error(msg)
  }
}
</script>

<style lang="scss" scoped>
input {
  &[type="text"], &[type="email"], &[type="password"] {
    width: 33%;
  }
}

.change-password {
  padding: 1.75rem 0;
}

.password-rules {
  margin-top: .75rem;
}

@media only screen and (max-width: 667px) {
  input {
    &[type="text"], &[type="email"], &[type="password"] {
      width: 100%;
      height: 32px;
    }
  }
}
</style>