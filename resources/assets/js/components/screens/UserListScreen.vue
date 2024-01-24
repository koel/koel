<template>
  <section id="usersWrapper">
    <ScreenHeader layout="collapsed">
      Users
      <ControlsToggle v-model="showingControls" />

      <template #controls>
        <BtnGroup v-if="showingControls || !isPhone" uppercased>
          <Btn class="btn-add" green @click="showAddUserForm">
            <Icon :icon="faPlus" />
            Add
          </Btn>
          <Btn class="btn-invite" orange @click="showInviteUserForm">Invite</Btn>
        </BtnGroup>
      </template>
    </ScreenHeader>

    <div v-koel-overflow-fade class="main-scroll-wrap">
      <ul class="users">
        <li v-for="user in users" :key="user.id">
          <UserCard :user="user" />
        </li>
      </ul>

      <template v-if="prospects.length">
        <h2 class="invited-heading" data-testid="prospects-heading">
          <i />
          <span>Invited</span>
          <i />
        </h2>

        <ul class="users">
          <li v-for="user in prospects" :key="user.id">
            <UserCard :user="user" />
          </li>
        </ul>
      </template>
    </div>
  </section>
</template>

<script lang="ts" setup>
import { faPlus } from '@fortawesome/free-solid-svg-icons'
import isMobile from 'ismobilejs'
import { computed, defineAsyncComponent, onMounted, ref, toRef } from 'vue'
import { userStore } from '@/stores'
import { eventBus } from '@/utils'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ControlsToggle from '@/components/ui/ScreenControlsToggle.vue'
import UserCard from '@/components/user/UserCard.vue'

const Btn = defineAsyncComponent(() => import('@/components/ui/Btn.vue'))
const BtnGroup = defineAsyncComponent(() => import('@/components/ui/BtnGroup.vue'))

const allUsers = toRef(userStore.state, 'users')
const users = computed(() => allUsers.value.filter(({ is_prospect }) => !is_prospect))
const prospects = computed(() => allUsers.value.filter(({ is_prospect }) => is_prospect))

const isPhone = isMobile.phone
const showingControls = ref(false)

const showAddUserForm = () => eventBus.emit('MODAL_SHOW_ADD_USER_FORM')
const showInviteUserForm = () => eventBus.emit('MODAL_SHOW_INVITE_USER_FORM')

onMounted(async () => await userStore.fetch())
</script>

<style lang="scss" scoped>
.users {
  display: grid;
  grid-gap: .7rem 1rem;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
}

.invited-heading {
  margin: 2rem 0 1rem;
  text-transform: uppercase;
  letter-spacing: .1rem;
  color: var(--color-text-secondary);
  text-align: center;
  position: relative;
  display: flex;
  justify-content: center;

  i {
    position: relative;
    flex: 1;

    &::before {
      content: '';
      position: absolute;
      top: 50%;
      left: 0;
      right: 0;
      height: 1px;
      background: var(--color-text-secondary);
      opacity: .2;
    }
  }

  span {
    padding: 0.2rem .8rem;
    position: relative;

    &::before {
      border: 1px solid var(--color-text-secondary);
      opacity: .2;
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      border-radius: 5px;
    }
  }
}
</style>
