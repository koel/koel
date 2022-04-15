import { defineAsyncComponent, ref } from 'vue'
import { playlistStore } from '@/stores'

export const useSmartPlaylistForms = (initialRuleGroups: SmartPlaylistRuleGroup[]) => {
  const Btn = defineAsyncComponent(() => import('@/components/ui/btn.vue'))
  const FormBase = defineAsyncComponent(() => import('@/components/playlist/smart-playlist/form-base.vue'))
  const RuleGroup = defineAsyncComponent(() => import('@/components/playlist/smart-playlist/rule-group.vue'))
  const SoundBar = defineAsyncComponent(() => import('@/components/ui/sound-bar.vue'))

  const ruleGroups = ref<SmartPlaylistRuleGroup[]>(initialRuleGroups)
  const loading = ref(false)

  const createGroup = () => playlistStore.createEmptySmartPlaylistRuleGroup()
  const addGroup = () => ruleGroups.value.push(createGroup())

  const onGroupChanged = (data: SmartPlaylistRuleGroup) => {
    const changedGroup = Object.assign(ruleGroups.value.find(g => g.id === data.id), data)

    // Remove empty group
    if (changedGroup.rules.length === 0) {
      ruleGroups.value = ruleGroups.value.filter(group => group.id !== changedGroup.id)
    }
  }

  const emit = defineEmits(['close'])
  const close = () => emit('close')

  return {
    Btn,
    FormBase,
    RuleGroup,
    SoundBar,
    ruleGroups,
    loading,
    addGroup,
    onGroupChanged,
    close
  }
}
