<template>
  <div class="add-user" @keydown.esc="maybeClose">
    <sound-bar v-if="loading"/>
    <form class="user-add" @submit.prevent="submit" v-else data-testid="add-user-form">
      <header>
        <h1>Add New User</h1>
      </header>

      <div>
        <div class="form-row">
          <label>Name</label>
          <input title="Name" type="text" name="name" v-model="newUser.name" required v-koel-focus>
        </div>
        <div class="form-row">
          <label>Email</label>
          <input title="Email" type="email" name="email" v-model="newUser.email" required>
        </div>
        <div class="form-row">
          <label>Password</label>
          <input
            title="Password"
            type="password"
            name="password"
            v-model="newUser.password"
            autocomplete="new-password"
            required
          >
          <p class="help">Min. 10 characters. Must be a mix of characters, numbers, and symbols.</p>
        </div>
        <div class="form-row">
          <label>
            <input type="checkbox" name="is_admin" v-model="newUser.is_admin"> User is an admin
            <tooltip-icon title="Admins can perform administrative tasks like managing users and uploading songs."/>
          </label>
        </div>
      </div>

      <footer>
        <btn class="btn-add" type="submit">Save</btn>
        <btn class="btn-cancel" @click.prevent="maybeClose" white>Cancel</btn>
      </footer>
    </form>
  </div>
</template>

<script lang="ts">
import Vue from 'vue'
import { isEqual } from 'lodash'
import { CreateUserData, userStore } from '@/stores'
import { alerts, parseValidationError } from '@/utils'

export default Vue.extend({
  components: {
    Btn: () => import('@/components/ui/btn.vue'),
    SoundBar: () => import('@/components/ui/sound-bar.vue'),
    TooltipIcon: () => import('@/components/ui/tooltip-icon.vue')
  },

  data: () => ({
    loading: false,
    emptyUserData: {
      name: '',
      email: '',
      password: '',
      is_admin: false
    } as CreateUserData,
    newUser: {} as CreateUserData
  }),

  methods: {
    async submit (): Promise<void> {
      this.loading = true

      try {
        await userStore.store(this.newUser)
        this.close()
      } catch (err) {
        const msg = err.response.status === 422 ? parseValidationError(err.response.data)[0] : 'Unknown error.'
        alerts.error(msg)
      } finally {
        this.loading = false
      }
    },

    close (): void {
      this.$emit('close')
    },

    maybeClose (): void {
      if (isEqual(this.newUser, this.emptyUserData)) {
        this.close()
        return
      }

      alerts.confirm('Discard all changes?', () => this.close())
    }
  },

  created () {
    Object.assign(this.newUser, this.emptyUserData)
  }
})
</script>

<style lang="scss" scoped>
.help {
  margin-top: .75rem;
}
</style>
