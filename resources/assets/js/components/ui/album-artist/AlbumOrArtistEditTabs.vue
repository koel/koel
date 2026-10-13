<template>
  <Tabs class="mt-1 -m-6">
    <TabList>
      <TabButton
        :id="detailsTabId"
        :selected="currentTab === 'details'"
        :aria-controls="detailsPanelId"
        @click="currentTab = 'details'"
      >
        Details
      </TabButton>
      <TabButton
        :id="descriptionTabId"
        :selected="currentTab === 'description'"
        :aria-controls="descriptionPanelId"
        @click="currentTab = 'description'"
      >
        Description
      </TabButton>
    </TabList>

    <TabPanelContainer>
      <TabPanel
        v-show="currentTab === 'details'"
        :id="detailsPanelId"
        :aria-labelledby="detailsTabId"
        class="space-y-5"
      >
        <slot />
      </TabPanel>

      <TabPanel
        v-show="currentTab === 'description'"
        :id="descriptionPanelId"
        :aria-labelledby="descriptionTabId"
        class="space-y-2"
      >
        <RichTextEditor v-model="description" label="Description" />
        <p class="text-k-fg-50">Leave empty to use the online-fetched text when applicable.</p>
      </TabPanel>
    </TabPanelContainer>
  </Tabs>
</template>

<script lang="ts" setup>
import { defineAsyncComponent, ref, useId } from 'vue'

import TabButton from '@/components/ui/tabs/TabButton.vue'
import TabList from '@/components/ui/tabs/TabList.vue'
import TabPanel from '@/components/ui/tabs/TabPanel.vue'
import TabPanelContainer from '@/components/ui/tabs/TabPanelContainer.vue'
import Tabs from '@/components/ui/tabs/Tabs.vue'

const RichTextEditor = defineAsyncComponent(() => import('@/components/ui/form/RichTextEditor.vue'))

const description = defineModel<string | undefined>('description', { required: true })

const id = useId()
const detailsTabId = `${id}-details-tab`
const descriptionTabId = `${id}-description-tab`
const detailsPanelId = `${id}-details`
const descriptionPanelId = `${id}-description`

const currentTab = ref<'details' | 'description'>('details')
</script>
