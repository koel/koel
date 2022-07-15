<template>
  <section id="usersWrapper">
    <ScreenHeader>
      Users
      <ControlsToggle :showing-controls="showingControls" @toggleControls="toggleControls"/>

      <template v-slot:controls>
        <BtnGroup uppercased v-if="showingControls || !isPhone">
          <Btn class="btn-add" data-testid="add-user-btn" green @click="showAddUserForm">
            <icon :icon="faPlus"/>
            Add
          </Btn>
        </BtnGroup>
      </template>
    </ScreenHeader>

    <div class="main-scroll-wrap">
      <ul class="users">
        <li v-for="user in users" :key="user.id">
          <UserCard :user="user"/>
        </li>
      </ul>
    </div>
  </section>
</template>

<script lang="ts" setup>
import { faPlus } from '@fortawesome/free-solid-svg-icons'
import isMobile from 'ismobilejs'
import { defineAsyncComponent, onMounted, ref, toRef } from 'vue'
import { userStore } from '@/stores'
import { eventBus } from '@/utils'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ControlsToggle from '@/components/ui/ScreenControlsToggle.vue'
import UserCard from '@/components/user/UserCard.vue'

const Btn = defineAsyncComponent(() => import('@/components/ui/Btn.vue'))
const BtnGroup = defineAsyncComponent(() => import('@/components/ui/BtnGroup.vue'))

const users = toRef(userStore.state, 'users')
const isPhone = isMobile.phone
const showingControls = ref(false)

const toggleControls = () => (showingControls.value = !showingControls.value)
const showAddUserForm = () => eventBus.emit('MODAL_SHOW_ADD_USER_FORM')

onMounted(async () => await userStore.fetch())
</script>

<style lang="scss" scoped>
.users {
  display: grid;
  grid-gap: .7rem 1rem;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
}
</style>
