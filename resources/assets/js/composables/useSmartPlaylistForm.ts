import type { Ref } from 'vue'
import { nextTick, ref } from 'vue'
import { playlistStore } from '@/stores/playlistStore'

import Btn from '@/components/ui/form/Btn.vue'
import RuleGroup from '@/components/playlist/smart-playlist/SmartPlaylistRuleGroup.vue'
import SoundBars from '@/components/ui/SoundBars.vue'

type SmartPlaylistFormTab = 'details' | 'rules'

export const useSmartPlaylistForm = (collectedRuleGroups: Ref<SmartPlaylistRuleGroup[]> = ref([])) => {
  const currentTab = ref<SmartPlaylistFormTab>('details')
  const activateTab = (tab: SmartPlaylistFormTab) => (currentTab.value = tab)
  const isTabActive = (tab: SmartPlaylistFormTab) => currentTab.value === tab

  const showTabOfInvalidField = async (event: Event) => {
    const field = event.target as HTMLInputElement
    const tab = field.closest<HTMLElement>('[data-tab]')?.dataset.tab as SmartPlaylistFormTab | undefined

    if (!tab || isTabActive(tab)) {
      return
    }

    activateTab(tab)
    await nextTick()
    field.reportValidity()
  }

  const addGroup = () => collectedRuleGroups.value.push(playlistStore.createEmptySmartPlaylistRuleGroup())

  const onGroupChanged = (data: SmartPlaylistRuleGroup) => {
    const changedGroup = Object.assign(
      collectedRuleGroups.value.find(({ id }) => id === data.id)!,
      data,
    )

    // Remove empty groups
    if (changedGroup.rules.length === 0) {
      collectedRuleGroups.value = collectedRuleGroups.value.filter(({ id }) => id !== changedGroup.id)
    }
  }

  return {
    Btn,
    RuleGroup,
    SoundBars,
    currentTab,
    activateTab,
    isTabActive,
    showTabOfInvalidField,
    collectedRuleGroups,
    addGroup,
    onGroupChanged,
  }
}
