<template>
  <ScreenBase>
    <template #header>
      <ScreenHeader layout="collapsed">
        Users

        <template #controls>
          <BtnGroup uppercase>
            <Btn variant="success" @click="showAddUserForm">
              <Icon :icon="faPlus" />
              Add
            </Btn>
            <Btn variant="highlight" v-if="canInvite" @click="showInviteUserForm">Invite</Btn>
          </BtnGroup>
        </template>
      </ScreenHeader>
    </template>

    <div class="flex flex-col gap-4">
      <SegmentedControl
        v-if="prospects.length"
        v-model="currentFilter"
        :options="filterOptions"
        class="self-center"
        name="user-filter"
      >
        <template #default="{ option }">
          {{ option.label }}
          <span
            :data-testid="`user-filter-count-${option.value}`"
            class="inline-flex items-center justify-center h-[16px] min-w-[16px] px-[5px] rounded-full bg-k-fg-10 text-[.8rem] leading-none tabular-nums"
            data-badge
          >
            {{ usersByFilter[option.value].length }}
          </span>
        </template>
      </SegmentedControl>

      <ul class="space-y-3">
        <li v-for="user in usersByFilter[currentFilter]" :key="user.id">
          <UserCard :user />
        </li>
      </ul>
    </div>
  </ScreenBase>
</template>

<script lang="ts" setup>
import { faPlus } from '@fortawesome/free-solid-svg-icons'
import { computed, onMounted, ref, toRef, watch } from 'vue'
import { userStore } from '@/stores/userStore'
import { defineAsyncComponent } from '@/utils/helpers'
import { useAuthorization } from '@/composables/useAuthorization'
import { useModal } from '@/composables/useModal'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import UserCard from '@/components/user/UserCard.vue'
import BtnGroup from '@/components/ui/form/BtnGroup.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'
import SegmentedControl from '@/components/ui/SegmentedControl.vue'

const Btn = defineAsyncComponent(() => import('@/components/ui/form/Btn.vue'))
const AddUserForm = defineAsyncComponent(() => import('@/components/user/AddUserForm.vue'))
const InviteUserForm = defineAsyncComponent(() => import('@/components/user/InviteUserForm.vue'))

const { openModal } = useModal()
const { currentUser } = useAuthorization()

const allUsers = toRef(userStore.state, 'users')

const users = computed(() =>
  allUsers.value
    .filter(({ is_prospect }) => !is_prospect)
    .sort((a, b) =>
      a.id === currentUser.value.id ? -1 : b.id === currentUser.value.id ? 1 : a.name.localeCompare(b.name),
    ),
)

const prospects = computed(() => allUsers.value.filter(({ is_prospect }) => is_prospect))

type UserFilter = 'active' | 'invited'

const currentFilter = ref<UserFilter>('active')

const usersByFilter = computed<Record<UserFilter, User[]>>(() => ({
  active: users.value,
  invited: prospects.value,
}))

const filterOptions: { value: UserFilter; label: string; testId: string }[] = [
  { value: 'active', label: 'Active', testId: 'user-filter-active' },
  { value: 'invited', label: 'Invited', testId: 'user-filter-invited' },
]

watch(prospects, () => {
  if (!prospects.value.length) {
    currentFilter.value = 'active'
  }
})

const canInvite = window.KOEL.mailer_configured

const showAddUserForm = () => openModal<'ADD_USER_FORM'>(AddUserForm)
const showInviteUserForm = () => openModal<'INVITE_USER_FORM'>(InviteUserForm)

onMounted(async () => await userStore.fetch())
</script>
