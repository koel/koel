<template>
  <SidebarSection v-if="visibleItems.length">
    <template #header>
      <SidebarSectionHeader>Manage</SidebarSectionHeader>
    </template>

    <ul class="menu">
      <SidebarItem
        v-for="item in visibleItems"
        :key="item.route"
        :href="url(item.route)"
        :active="isCurrentScreen(...item.screens)"
        :disabled-reason="item.disabledReason"
      >
        <template #icon>
          <Icon :icon="item.busy ? faSpinner : item.icon" :spin="item.busy" fixed-width />
        </template>
        <template v-if="item.badgeLabel" #badge>{{ item.badgeLabel }}</template>
        {{ item.label }}
        <span aria-live="polite" class="sr-only">{{ item.busy ? 'in progress' : '' }}</span>
      </SidebarItem>
    </ul>
  </SidebarSection>
</template>

<script lang="ts" setup>
import { computed } from 'vue'
import { faSpinner, faTools, faUpload } from '@fortawesome/free-solid-svg-icons'
import type { IconDefinition } from '@fortawesome/fontawesome-svg-core'
import type { RouteName } from '@/config/routes'
import { useRouter } from '@/composables/useRouter'
import { usePolicies } from '@/composables/usePolicies'
import { useUpload } from '@/composables/useUpload'
import { uploadService } from '@/services/uploadService'
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
  badge?: () => string | null
  isBusy?: () => boolean
  disabledReason?: () => string | null
}

const { url, isCurrentScreen } = useRouter()
const { currentUserCan } = usePolicies()
const { allowsUpload } = useUpload()

const items = computed(() =>
  applyFilters<ManageSidebarItem[]>(Filter.MANAGE_SIDEBAR_ITEMS, [
    {
      label: 'Settings',
      icon: faTools,
      route: 'settings',
      screens: ['Settings'],
      visible: () => currentUserCan.manageSettings() || currentUserCan.manageUsers(),
    },
    {
      label: 'Upload',
      icon: faUpload,
      route: 'upload',
      screens: ['Upload'],
      visible: () => allowsUpload.value,
      isBusy: () => uploadService.getUnfinishedFiles().length > 0,
    },
  ]),
)

const visibleItems = computed(() =>
  items.value
    .filter(item => item.visible())
    .map(item => ({
      ...item,
      badgeLabel: item.badge?.() ?? null,
      busy: item.isBusy?.() ?? false,
      disabledReason: item.disabledReason?.() ?? null,
    })),
)
</script>
