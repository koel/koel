<template>
  <form @submit.prevent="update" data-testid="update-profile-form">
    <div class="form-row">
      <label for="inputProfileCurrentPassword">Current Password</label>
      <input
        v-model="profile.current_password"
        name="current_password"
        type="password"
        id="inputProfileCurrentPassword"
        v-koel-focus
        required
      >
    </div>

    <div class="form-row">
      <label for="inputProfileName">Name</label>
      <input type="text" name="name" id="inputProfileName" v-model="profile.name" required>
    </div>

    <div class="form-row">
      <label for="inputProfileEmail">Email Address</label>
      <input type="email" name="email" id="inputProfileEmail" v-model="profile.email" required>
    </div>

    <div class="change-password">
      <div class="form-row">
        <label for="inputProfileNewPassword">New Password</label>
        <input
          v-model="profile.new_password"
          name="new_password"
          type="password"
          id="inputProfileNewPassword"
          autocomplete="new-password"
        >
        <p class="password-rules help">
          Min. 10 characters. Must be a mix of characters, numbers, and symbols.<br>
          Leave this empty to keep the current password.
        </p>
      </div>
    </div>

    <div class="form-row">
      <Btn type="submit" class="btn-submit">Save</Btn>
      <span v-if="demo" style="font-size:.95rem; opacity:.7; margin-left:5px">
        Changes will not be saved in the demo version.
      </span>
    </div>
  </form>
</template>

<script lang="ts" setup>
import { defineAsyncComponent, onMounted, reactive, ref } from 'vue'
import { sharedStore, UpdateCurrentProfileData, userStore } from '@/stores'
import { alerts, parseValidationError } from '@/utils'

const Btn = defineAsyncComponent(() => import('@/components/ui/btn.vue'))

const demo = NODE_ENV === 'demo'
const state = reactive(userStore.state)
const sharedState = reactive(sharedStore.state)
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
