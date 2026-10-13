<template>
  <Tabs class="mt-1 -m-6" @invalid.capture="showTabOfInvalidField">
    <TabList>
      <TabButton
        :id="detailsTabId"
        :selected="isTabActive('details')"
        :aria-controls="detailsPanelId"
        @click="activateTab('details')"
      >
        Details
      </TabButton>
      <TabButton
        :id="rulesTabId"
        :selected="isTabActive('rules')"
        :aria-controls="rulesPanelId"
        @click="activateTab('rules')"
      >
        Rules
      </TabButton>
    </TabList>

    <TabPanelContainer>
      <TabPanel
        v-show="isTabActive('details')"
        :id="detailsPanelId"
        data-tab="details"
        :aria-labelledby="detailsTabId"
        class="space-y-5"
      >
        <slot />
      </TabPanel>

      <TabPanel
        v-show="isTabActive('rules')"
        :id="rulesPanelId"
        data-tab="rules"
        :aria-labelledby="rulesTabId"
        class="space-y-5"
      >
        <div class="scroll-mask-y group-container space-y-5 overflow-auto max-h-[480px]">
          <RuleGroup
            v-for="(group, index) in ruleGroups"
            :key="group.id"
            :group="group"
            :is-first-group="index === 0"
            @input="onGroupChanged"
          />
          <Btn size="small" variant="success" class="btn-add-group" uppercase @click.prevent="addGroup">
            <Icon :icon="faPlus" />
            Group
          </Btn>
        </div>
      </TabPanel>
    </TabPanelContainer>
  </Tabs>
</template>

<script lang="ts" setup>
import { faPlus } from '@fortawesome/free-solid-svg-icons'
import { useId } from 'vue'
import { useSmartPlaylistForm } from '@/composables/useSmartPlaylistForm'

import TabButton from '@/components/ui/tabs/TabButton.vue'
import TabList from '@/components/ui/tabs/TabList.vue'
import TabPanel from '@/components/ui/tabs/TabPanel.vue'
import TabPanelContainer from '@/components/ui/tabs/TabPanelContainer.vue'
import Tabs from '@/components/ui/tabs/Tabs.vue'

const ruleGroups = defineModel<SmartPlaylistRuleGroup[]>('ruleGroups', { required: true })

const id = useId()
const detailsTabId = `${id}-details-tab`
const rulesTabId = `${id}-rules-tab`
const detailsPanelId = `${id}-details`
const rulesPanelId = `${id}-rules`

const { Btn, RuleGroup, activateTab, isTabActive, showTabOfInvalidField, addGroup, onGroupChanged } =
  useSmartPlaylistForm(ruleGroups)
</script>

<style lang="postcss" scoped>
.group-container {
  scrollbar-gutter: stable;
}
</style>
