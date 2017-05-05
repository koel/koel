<template>
  <section id="usersWrapper">
    <h1 class="heading">
      <span>Users
        <i class="fa fa-angle-down toggler" v-show="isPhone && !showingControls" @click="showingControls = true"/>
        <i class="fa fa-angle-up toggler" v-show="isPhone && showingControls" @click.prevent="showingControls = false"/>
      </span>

      <div class="buttons" v-show="!isPhone || showingControls">
        <button class="btn btn-green btn-add" @click="addUser">
          <i class="fa fa-plus"></i>
          Add</button>
      </div>
    </h1>

    <div class="main-scroll-wrap">
      <div class="users">
        <user-item v-for="user in state.users" :user="user" @editUser="editUser"/>
        <article class="user-item" v-for="n in 6"/>
      </div>
    </div>

    <edit-user-form ref="editUserForm"/>
    <add-user-form ref="addUserForm"/>
  </section>
</template>

<script>
import isMobile from 'ismobilejs'

import { userStore } from '../../../stores'
import userItem from '../../shared/user-item.vue'
import editUserForm from '../../modals/edit-user-form.vue'
import addUserForm from '../../modals/add-user-form.vue'

export default {
  components: { userItem, editUserForm, addUserForm },

  data () {
    return {
      state: userStore.state,
      isPhone: isMobile.phone,
      showingControls: false
    }
  },

  methods: {
    /**
     * Open the "Add User" form.
     */
    addUser () {
      this.$refs.addUserForm.open()
    },

    /**
     * Open the "Edit User" form.
     *
     * @param  {Object} user
     */
    editUser (user) {
      this.$refs.editUserForm.open(user)
    }
  }
}
</script>

<style lang="scss">
@import "../../../../sass/partials/_vars.scss";
@import "../../../../sass/partials/_mixins.scss";

#usersWrapper {
  .users {
    justify-content: space-between;
    flex-wrap: wrap;
    display: flex;
  }

  button {
    margin-right: 3px;
  }

  @media only screen and (max-width: 768px) {
    .users {
      flex-direction: column;

      .buttons {
        margin-top: 12px;
        display: block;
      }
    }
  }
}

.user-item {
  width: 32%;
  margin-bottom: 16px;

  .info {
    display: flex;

    img {
      flex: 0 0 128px;
    }

    .right {
      flex: 1;
      padding: 16px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      background-color: rgba(255, 255, 255, .02);
    }

    h1 {
      font-size: 1.4rem;
      margin-bottom: 5px;

      .you {
        color: $colorHighlight;
        margin-left: 5px;
      }
    }

    .buttons {
      display: none;
      margin-top: 16px;
    }

    &:hover, html.touchevents & {
      .buttons {
        display: block;
      }
    }
  }

  html.with-extra-panel & {
    width: 49%;
  }

  @media only screen and (max-width: 1024px) {
    width: 100%;
  }
}
</style>
