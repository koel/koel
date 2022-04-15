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
      <btn type="submit" class="btn-submit">Save</btn>
      <span v-if="demo" style="font-size:.95rem; opacity:.7; margin-left:5px">
        Changes will not be saved in the demo version.
      </span>
    </div>
  </form>
</template>

<script lang="ts">
import Vue from 'vue'
import { preferenceStore as preferences, sharedStore, UpdateCurrentProfileData, userStore } from '@/stores'
import { alerts, parseValidationError } from '@/utils'

export default Vue.extend({
  components: {
    Btn: () => import('@/components/ui/btn.vue')
  },

  data: () => ({
    preferences,
    demo: NODE_ENV === 'demo',
    state: userStore.state,
    sharedState: sharedStore.state,
    profile: {} as UpdateCurrentProfileData
  }),

  methods: {
    async update (): Promise<void> {
      try {
        await userStore.updateProfile(this.profile)
        this.profile.current_password = null
        delete this.profile.new_password
      } catch (err) {
        const msg = err.response.status === 422 ? parseValidationError(err.response.data)[0] : 'Unknown error.'
        alerts.error(msg)
      }
    }
  },

  mounted () {
    this.profile = {
      name: userStore.current.name,
      email: userStore.current.email,
      current_password: null
    }
  }
})
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

@media only screen and (max-width : 667px) {
  input {
    &[type="text"], &[type="email"], &[type="password"] {
      width: 100%;
      height: 32px;
    }
  }
}
</style>
