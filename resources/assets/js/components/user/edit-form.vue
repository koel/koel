<template>
  <div class="edit-user" @keydown.esc="maybeClose">
    <sound-bar v-if="loading"/>
    <form class="user-edit" @submit.prevent="submit" v-else data-testid="edit-user-form">
      <header>
        <h1>Edit User</h1>
      </header>

      <div>
        <div class="form-row">
          <label>Name</label>
          <input title="Name" type="text" name="name" v-model="updateData.name" required v-koel-focus>
        </div>
        <div class="form-row">
          <label>Email</label>
          <input title="Email" type="email" name="email" v-model="updateData.email" required>
        </div>
        <div class="form-row">
          <label>Password</label>
          <input
            name="password"
            placeholder="Leave blank for no changes"
            type="password"
            v-model="updateData.password"
            autocomplete="new-password"
          >
          <p class="help">Min. 10 characters. Must be a mix of characters, numbers, and symbols.</p>
        </div>
        <div class="form-row">
          <label>
            <input type="checkbox" name="is_admin" v-model="updateData.is_admin"> User is an admin
            <tooltip-icon title="Admins can perform administrative tasks like managing users and uploading songs."/>
          </label>
        </div>
      </div>

      <footer>
        <btn class="btn-update" type="submit">Update</btn>
        <btn class="btn-cancel" @click.prevent="maybeClose" white data-test="cancel-btn">Cancel</btn>
      </footer>
    </form>
  </div>
</template>

<script lang="ts">
import { isEqual } from 'lodash'
import { alerts, parseValidationError } from '@/utils'
import { UpdateUserData, userStore } from '@/stores'
import Vue, { PropOptions } from 'vue'

export default Vue.extend({
  components: {
    Btn: () => import('@/components/ui/btn.vue'),
    SoundBar: () => import('@/components/ui/sound-bar.vue'),
    TooltipIcon: () => import('@/components/ui/tooltip-icon.vue')
  },

  props: {
    user: {
      type: Object,
      required: true
    } as PropOptions<User>
  },

  data: () => ({
    loading: false,
    updateData: {} as UpdateUserData,
    originalData: {} as UpdateUserData
  }),

  methods: {
    async submit (): Promise<void> {
      this.loading = true

      try {
        await userStore.update(this.user, this.updateData)
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
      if (isEqual(this.originalData, this.updateData)) {
        this.close()
        return
      }

      alerts.confirm('Discard all changes?', () => this.close())
    }
  },

  mounted (): void {
    this.updateData = {
      name: this.user.name,
      email: this.user.email,
      is_admin: this.user.is_admin
    }

    Object.assign(this.originalData, this.updateData)
  }
})
</script>

<style lang="scss" scoped>
.help {
  margin-top: .75rem;
}
</style>
