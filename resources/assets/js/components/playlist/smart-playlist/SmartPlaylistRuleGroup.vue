<template>
  <div class="rule-group" data-test="smart-playlist-rule-group">
    <div class="group-banner">
      <span v-if="isFirstGroup">
        Include songs that match <strong>all</strong> of these criteria
      </span>
      <span v-else>
        or <strong>all</strong> of these criteria
      </span>
    </div>

    <Rule
      :key="rule.id"
      :rule="rule"
      @input="onRuleChanged"
      @remove="removeRule(rule)"
      v-for="rule in mutatedGroup.rules"
    />

    <Btn @click.prevent="addRule" class="btn-add-rule" green small uppercase>
      <i class="fa fa-plus"></i>
      Rule
    </Btn>
  </div>
</template>

<script lang="ts" setup>
import { defineAsyncComponent, reactive, toRefs } from 'vue'
import { playlistStore } from '@/stores'

const props = defineProps<{ group: SmartPlaylistRuleGroup, isFirstGroup: boolean }>()
const { group, isFirstGroup } = toRefs(props)

const Btn = defineAsyncComponent(() => import('@/components/ui/btn.vue'))
const Rule = defineAsyncComponent(() => import('@/components/playlist/smart-playlist/SmartPlaylistRule.vue'))

const mutatedGroup = reactive<SmartPlaylistRuleGroup>(JSON.parse(JSON.stringify(group.value)))

const emit = defineEmits(['input'])

const notifyParentForUpdate = () => emit('input', mutatedGroup)

const addRule = () => mutatedGroup.rules.push(playlistStore.createEmptySmartPlaylistRule())

const onRuleChanged = (data: SmartPlaylistRule) => {
  Object.assign(mutatedGroup.rules.find(r => r.id === data.id), data)
  notifyParentForUpdate()
}

const removeRule = (rule: SmartPlaylistRule) => {
  mutatedGroup.rules = mutatedGroup.rules.filter(r => r.id !== rule.id)
  notifyParentForUpdate()
}
</script>

<style lang="scss" scoped>
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
