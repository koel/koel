<template>
  <article class="user-card" :class="{ me: isCurrentUser }" data-test="user-card">
    <div class="info">
      <img :src="user.avatar" width="96" height="96" :alt="`${user.name}'s avatar`">

      <div class="right">
        <div>
          <h1>
            <span class="name">{{ user.name }}</span>
            <i
              v-if="isCurrentUser"
              class="you text-orange fa fa-check-circle"
              title="This is you!"
              data-test="current-user-indicator"
            />
            <i
              v-if="user.is_admin"
              class="is-admin text-blue fa fa-shield"
              title="User has admin privileges"
              data-test="admin-indicator"
            />
          </h1>
          <p class="email" data-test="user-email">{{ user.email }}</p>
        </div>

        <div class="buttons">
          <btn class="btn-edit" @click="edit" small data-test="edit-user-btn">{{ editButtonLabel }}</btn>
          <btn
            v-if="!isCurrentUser"
            class="btn-delete"
            red
            @click="confirmDelete"
            small
            data-test="delete-user-btn"
          >
            Delete
          </btn>
        </div>
      </div>
    </div>
  </article>
</template>

<script lang="ts">
import Vue, { PropOptions } from 'vue'
import { userStore } from '@/stores'
import router from '@/router'
import { alerts } from '@/utils'

export default Vue.extend({
  components: {
    Btn: () => import('@/components/ui/btn.vue')
  },

  props: {
    user: {
      type: Object,
      required: true
    } as PropOptions<User>
  },

  data: () => ({
    confirmingDelete: false
  }),

  computed: {
    isCurrentUser (): boolean {
      return this.user.id === userStore.current.id
    },

    editButtonLabel (): string {
      return this.isCurrentUser ? 'Update Profile' : 'Edit'
    }
  },

  methods: {
    /**
     * Trigger editing a user.
     * If the user is the current logged-in user, redirect to the profile screen instead.
     */
    edit (): void {
      if (this.isCurrentUser) {
        router.go('profile')
      } else {
        this.$emit('editUser', this.user)
      }
    },

    confirmDelete (): void {
      alerts.confirm(`You’re about to unperson ${this.user.name}. Are you sure?`, this.destroy)
    },

    destroy (): void {
      userStore.destroy(this.user)
      this.$destroy()
    }
  }
})
</script>

<style lang="scss" scoped>
.user-card {
  width: 100%;

  .info {
    display: flex;

    img {
      flex: 0 0 96px;
    }

    .email {
      opacity: .5;
    }

    .right {
      flex: 1;
      padding: 1rem;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      background-color: rgba(255, 255, 255, .02);
      position: relative;
    }

    h1 {
      font-size: 1.4rem;
      margin-bottom: .25rem;

      > * + * {
        margin-left: .5rem
      }
    }

    .buttons {
      display: none;
      margin-top: .5rem;

      @media (hover: none) {
        display: block;
      }
    }

    &:hover .buttons {
      display: block;
    }
  }

  @media only screen and (max-width: 1024px) {
    width: 100%;
  }
}
</style>
