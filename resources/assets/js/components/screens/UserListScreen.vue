<template>
  <section id="usersWrapper">
    <ScreenHeader>
      Users
      <ControlsToggler :showing-controls="showingControls" @toggleControls="toggleControls"/>

      <template v-slot:controls>
        <BtnGroup uppercased v-if="showingControls || !isPhone">
          <Btn class="btn-add" data-testid="add-user-btn" green @click="showAddUserForm">
            <i class="fa fa-plus"></i>
            Add
          </Btn>
        </BtnGroup>
      </template>
    </ScreenHeader>

    <div class="main-scroll-wrap">
      <div class="users">
        <UserCard v-for="user in users" :key="user.id" :user="user" @editUser="showEditUserForm"/>
      </div>
    </div>
  </section>
</template>

<script lang="ts" setup>
import isMobile from 'ismobilejs'
import { defineAsyncComponent, ref, toRef } from 'vue'

import { userStore } from '@/stores'
import { eventBus } from '@/utils'

const ScreenHeader = defineAsyncComponent(() => import('@/components/ui/ScreenHeader.vue'))
const ControlsToggler = defineAsyncComponent(() => import('@/components/ui/ScreenControlsToggler.vue'))
const Btn = defineAsyncComponent(() => import('@/components/ui/Btn.vue'))
const BtnGroup = defineAsyncComponent(() => import('@/components/ui/BtnGroup.vue'))
const UserCard = defineAsyncComponent(() => import('@/components/user/UserCard.vue'))

const users = toRef(userStore.state, 'users')
const isPhone = isMobile.phone
const showingControls = ref(false)

const toggleControls = () => (showingControls.value = !showingControls.value)
const showAddUserForm = () => eventBus.emit('MODAL_SHOW_ADD_USER_FORM')
const showEditUserForm = (user: User) => eventBus.emit('MODAL_SHOW_EDIT_USER_FORM', user)
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
