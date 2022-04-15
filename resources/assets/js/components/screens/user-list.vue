<template>
  <section id="usersWrapper">
    <screen-header>
      Users
      <controls-toggler :showing-controls="showingControls" @toggleControls="toggleControls"/>

      <template v-slot:controls>
        <btn-group uppercased v-if="showingControls || !isPhone">
          <btn class="btn-add" @click="showAddUserForm" green data-testid="add-user-btn">
            <i class="fa fa-plus"></i>
            Add
          </btn>
        </btn-group>
      </template>
    </screen-header>

    <div class="main-scroll-wrap">
      <div class="users">
        <user-card v-for="user in state.users" :user="user" @editUser="showEditUserForm" :key="user.id"/>
      </div>
    </div>
  </section>
</template>

<script lang="ts">
import Vue from 'vue'
import isMobile from 'ismobilejs'

import { userStore } from '@/stores'
import { eventBus } from '@/utils'

export default Vue.extend({
  components: {
    ScreenHeader: () => import('@/components/ui/screen-header.vue'),
    ControlsToggler: () => import('@/components/ui/screen-controls-toggler.vue'),
    Btn: () => import('@/components/ui/btn.vue'),
    BtnGroup: () => import('@/components/ui/btn-group.vue'),
    UserCard: () => import('@/components/user/card.vue')
  },

  data: () => ({
    state: userStore.state,
    isPhone: isMobile.phone,
    showingControls: false
  }),

  methods: {
    toggleControls (): void {
      this.showingControls = !this.showingControls
    },

    showAddUserForm: (): void => {
      eventBus.emit('MODAL_SHOW_ADD_USER_FORM')
    },

    showEditUserForm: (user: User): void => {
      eventBus.emit('MODAL_SHOW_EDIT_USER_FORM', user)
    }
  }
})
</script>

<style lang="scss">
#usersWrapper {
  .users {
    display: grid;
    grid-gap: .7rem 1rem;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  }
}
</style>
