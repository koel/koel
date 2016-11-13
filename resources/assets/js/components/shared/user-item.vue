<template>
  <article class="user-item" :class="{ editing: editing }">
    <div class="info" v-if="!editing">
      <img :src="user.avatar" width="128" height="128">

      <div class="right">
        <div>
          <h1>{{ user.name }}
            <i v-if="isCurrentUser" class="you fa fa-check-circle"/>
          </h1>
          <p>{{ user.email }}</p>
        </div>

        <div class="buttons">
          <button class="btn btn-blue" @click="edit">
            {{ isCurrentUser ? 'Update Profile' : 'Edit' }}
          </button>
          <button class="btn btn-red" @click="del">Delete</button>
        </div>
      </div>
    </div>

    <form class="edit" @submit.prevent="update" v-else>
      <div class="input-row">
        <label>Name</label>
        <input type="text" v-model="user.name" required v-koel-focus="editing">
      </div>
      <div class="input-row">
        <label>Email</label>
        <input type="email" v-model="user.email" required>
      </div>
      <div class="input-row">
        <label>Password</label>
        <input type="password" v-model="user.password" placeholder="Leave blank for no changes">
      </div>
      <div class="input-row">
        <label></label>
        <button class="btn btn-green">Update</button>
        <button class="btn btn-red" @click.prevent="cancelEdit">Cancel</button>
      </div>
    </form>
  </article>
</template>

<script>
import { clone, assign } from 'lodash';
import swal from 'sweetalert';

import { userStore } from '../../stores';
import router from '../../router';

export default {
  props: ['user'],

  data() {
    return {
      editing: false,
      confirmingDelete: false,
      cached: {},
    };
  },

  computed: {
    /**
     * Determine if the current logged in user is the user bound to this component.
     *
     * @return {Boolean}
     */
    isCurrentUser() {
      return this.user.id === userStore.current.id;
    },
  },

  methods: {
    /**
     * Trigger editing a user.
     * If the user is the current logged-in user, redirect to the profile screen instead.
     */
    edit() {
      if (this.isCurrentUser) {
        router.go('profile');

        return;
      }

      // Keep a cached version of the user for rolling back.
      this.cached = clone(this.user);
      this.editing = true;
    },

    /**
     * Cancel editing a user.
     */
    cancelEdit() {
      // Restore the original user's properties
      assign(this.user, this.cached);
      this.editing = false;
    },

    /**
     * Update the edited user.
     */
    update() {
      userStore.update(this.user, this.user.name, this.user.email, this.user.password). then(u => {
        this.editing = false;
      });
    },

    /**
     * Kill off the freaking user.
     */
    del() {
      swal({
        title: 'Hey…',
        text: `You’re about to unperson ${this.user.name}. Are you sure?`,
        type: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Certainly',
        cancelButtonText: 'Oops',
      }, () => {
        userStore.destroy(this.user).then(() => {
          this.$destroy(true);
        });
      });
    },
  },
};
</script>

<style lang="sass">
@import "../../../sass/partials/_vars.scss";
@import "../../../sass/partials/_mixins.scss";
</style>
