<template>
  <SidebarSection>
    <template #header>
      <SidebarSectionHeader>Manage</SidebarSectionHeader>
    </template>

    <ul class="menu">
      <SidebarItem
        v-for="item in visibleItems"
        :key="item.route"
        :href="url(item.route)"
        :active="isCurrentScreen(...item.screens)"
      >
        <template #icon>
          <Icon :icon="item.icon" fixed-width />
        </template>
        {{ item.label }}
      </SidebarItem>
    </ul>
  </SidebarSection>
</template>

<script lang="ts" setup>
import { computed } from 'vue'
import { faTools, faUpload, faUsers } from '@fortawesome/free-solid-svg-icons'
import type { IconDefinition } from '@fortawesome/fontawesome-svg-core'
import type { RouteName } from '@/config/routes'
import { useRouter } from '@/composables/useRouter'
import { usePolicies } from '@/composables/usePolicies'
import { Filter } from '@/config/hooks'
import { applyFilters } from '@/hooks'

import SidebarSection from '@/components/layout/main-wrapper/sidebar/SidebarSection.vue'
import SidebarSectionHeader from '@/components/layout/main-wrapper/sidebar/SidebarSectionHeader.vue'
import SidebarItem from '@/components/layout/main-wrapper/sidebar/SidebarItem.vue'

export interface ManageSidebarItem {
  label: string
  icon: IconDefinition
  route: RouteName
  screens: ScreenName[]
  visible: () => boolean
}

const { url, isCurrentScreen } = useRouter()
const { currentUserCan } = usePolicies()

const items = computed(() =>
  applyFilters<ManageSidebarItem[]>(Filter.MANAGE_SIDEBAR_ITEMS, [
    {
      label: 'Settings',
      icon: faTools,
      route: 'settings',
      screens: ['Settings'],
      visible: () => currentUserCan.manageSettings(),
    },
    {
      label: 'Upload',
      icon: faUpload,
      route: 'upload',
      screens: ['Upload'],
      visible: () => currentUserCan.uploadSongs(),
    },
    {
      label: 'Users',
      icon: faUsers,
      route: 'users.index',
      screens: ['Users', 'Profile'],
      visible: () => currentUserCan.manageUsers(),
    },
  ]),
)

const visibleItems = computed(() => items.value.filter(item => item.visible()))
</script>
