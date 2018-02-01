<template>
  <article class="user-item" :class="{ me: isCurrentUser }">
    <div class="info">
      <img :src="user.avatar" width="128" height="128">

      <div class="right">
        <div>
          <h1>{{ user.name }}
            <i v-if="isCurrentUser" class="you fa fa-check-circle"/>
          </h1>
          <p>{{ user.email }}</p>
        </div>

        <div class="buttons">
          <button class="btn btn-blue btn-edit" @click="edit">
            {{ isCurrentUser ? 'Update Profile' : 'Edit' }}
          </button>
          <button v-if="!isCurrentUser" class="btn btn-red btn-delete" @click="del">Delete</button>
        </div>
      </div>
    </div>
  </article>
</template>

<script>
import { userStore } from '@/stores'
import router from '@/router'
import { alerts } from '@/utils'

export default {
  name: 'shared--user-item',

  props: {
    user: {
      type: Object,
      required: true
    }
  },

  data () {
    return {
      confirmingDelete: false
    }
  },

  computed: {
    /**
     * Determine if the current logged in user is the user bound to this component.
     *
     * @return {Boolean}
     */
    isCurrentUser () {
      return this.user.id === userStore.current.id
    }
  },

  methods: {
    /**
     * Trigger editing a user.
     * If the user is the current logged-in user, redirect to the profile screen instead.
     */
    edit () {
      this.isCurrentUser ? router.go('profile') : this.$emit('editUser', this.user)
    },

    /**
     * Kill off the freaking user.
     */
    del () {
      alerts.confirm(`You’re about to unperson ${this.user.name}. Are you sure?`, this.doDelete)
    },

    doDelete () {
      userStore.destroy(this.user)
      this.$destroy()
    }
  }
}
</script>

<style lang="scss">
@import "~#/partials/_vars.scss";
@import "~#/partials/_mixins.scss";
</style>
