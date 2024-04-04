<template>
  <div class="rule-group" data-testid="smart-playlist-rule-group">
    <div class="group-banner">
      <span v-if="isFirstGroup">
        Include songs that match <strong>all</strong> of these criteria
      </span>
      <span v-else>
        or <strong>all</strong> of these criteria
      </span>
    </div>

    <Rule
      v-for="rule in mutatedGroup.rules"
      :key="rule.id"
      :rule="rule"
      @input="onRuleChanged"
      @remove="removeRule(rule)"
    />

    <Btn class="btn-add-rule" green small uppercase @click.prevent="addRule">
      <Icon :icon="faPlus" />
      Rule
    </Btn>
  </div>
</template>

<script lang="ts" setup>
import { faPlus } from '@fortawesome/free-solid-svg-icons'
import { defineAsyncComponent, reactive, toRefs } from 'vue'
import { playlistStore } from '@/stores'

const props = defineProps<{ group: SmartPlaylistRuleGroup, isFirstGroup: boolean }>()
const { group, isFirstGroup } = toRefs(props)

const Btn = defineAsyncComponent(() => import('@/components/ui/Btn.vue'))
const Rule = defineAsyncComponent(() => import('@/components/playlist/smart-playlist/SmartPlaylistRule.vue'))

const mutatedGroup = reactive<SmartPlaylistRuleGroup>(JSON.parse(JSON.stringify(group.value)))

const emit = defineEmits<{ (e: 'input', group: SmartPlaylistRuleGroup): void }>()

const notifyParentForUpdate = () => emit('input', mutatedGroup)

const addRule = () => mutatedGroup.rules.push(playlistStore.createEmptySmartPlaylistRule())

const onRuleChanged = (data: SmartPlaylistRule) => {
  Object.assign(mutatedGroup.rules.find(({ id }) => id === data.id)!, data)
  notifyParentForUpdate()
}

const removeRule = (rule: SmartPlaylistRule) => {
  mutatedGroup.rules = mutatedGroup.rules.filter(({ id }) => id !== rule.id)
  notifyParentForUpdate()
}
</script>

<style lang="postcss" scoped>
.rule-group {
  margin-bottom: 1rem;
  padding-bottom: .5rem;
  border-bottom: 1px solid rgba(255, 255, 255, .1);

  > * + * {
    margin-bottom: .5rem;
  }
}

.group-banner {
  margin-bottom: 1rem;
}
</style>
