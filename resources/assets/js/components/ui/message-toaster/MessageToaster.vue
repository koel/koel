<template>
  <div ref="root" class="popover">
    <TransitionGroup class="flex flex-col items-end gap-2" name="toast" tag="ul" @after-leave="hideWhenEmpty">
      <li v-for="message in messages" :key="message.id">
        <MessageToast :message="message" @dismiss="dismiss(message.id)" />
      </li>
    </TransitionGroup>
  </div>
  <div aria-live="polite" class="sr-only" data-testid="toast-announcements">
    <p v-for="message in messages" :key="message.id">{{ message.content }}</p>
  </div>
</template>

<script lang="ts" setup>
import type { Component } from 'vue'
import { computed, markRaw, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { uuid } from '@/utils/crypto'

import MessageToast from '@/components/ui/message-toaster/MessageToast.vue'

const DEFAULT_TIMEOUT = 5

const SETTLED_DEFAULTS = {
  icon: undefined,
  component: undefined,
  props: undefined,
  actions: undefined,
  progress: undefined,
  persistent: undefined,
  onDismiss: undefined,
  timeout: DEFAULT_TIMEOUT,
}

const root = ref<
  HTMLDivElement & {
    popover?: 'manual' | 'auto'
    showPopover?: () => void
  }
>()

const messages = ref<ToastMessage[]>([])
const hasLoadingToast = computed(() => messages.value.some(({ type }) => type === 'loading'))

const warnBeforeLeaving = (event: BeforeUnloadEvent) => {
  event.preventDefault()
  event.returnValue = ''
}

const withRawComponents = <T extends { icon?: Component; component?: Component }>(fields: T) => ({
  ...fields,
  ...(fields.icon && { icon: markRaw(fields.icon) }),
  ...(fields.component && { component: markRaw(fields.component) }),
})

const toMessageFields = (details: ToastDetails) =>
  typeof details === 'string' ? { content: details } : withRawComponents(details)

const resolveOutcome = <T>(outcome: ToastOutcome<T>, value: T) =>
  typeof outcome === 'function' ? outcome(value) : outcome

let toasterShown = false

const add = (type: ToastType, details: ToastDetails, timeout = DEFAULT_TIMEOUT) => {
  if (!toasterShown) {
    root.value?.showPopover?.()
    toasterShown = true
  }

  const id = uuid()
  messages.value.push({ id, type, timeout, ...toMessageFields(details) })

  return id
}

const update = (id: string, changes: Partial<Omit<ToastMessage, 'id'>>) => {
  const message = messages.value.find(({ id: messageId }) => messageId === id)

  if (!message) {
    return
  }

  Object.assign(message, withRawComponents(changes))
}

const dismiss = (id: string) => {
  const message = messages.value.find(({ id: messageId }) => messageId === id)

  if (!message) {
    return
  }

  messages.value = messages.value.filter(({ id: messageId }) => messageId !== id)
  message.onDismiss?.()
}

const hideWhenEmpty = () => {
  if (messages.value.length) {
    return
  }

  root.value?.hidePopover?.()
  toasterShown = false
}

const promise = <T>(pending: Promise<T>, outcomes: ToastPromiseMessages<T>) => {
  const id = add('loading', outcomes.loading)

  const settle = (type: ToastType, details: ToastDetails) =>
    update(id, { ...SETTLED_DEFAULTS, type, ...toMessageFields(details) })

  pending.then(
    value => {
      const warning = outcomes.warning?.(value)

      if (warning) {
        settle('warning', warning)
        return
      }

      settle('success', resolveOutcome(outcomes.success, value))
    },
    (error: unknown) => settle('danger', resolveOutcome(outcomes.error, error)),
  )

  return id
}

const info = (details: ToastDetails, timeout?: number) => add('info', details, timeout)
const success = (details: ToastDetails, timeout?: number) => add('success', details, timeout)
const warning = (details: ToastDetails, timeout?: number) => add('warning', details, timeout)
const error = (details: ToastDetails, timeout?: number) => add('danger', details, timeout)
const loading = (details: ToastDetails) => add('loading', details)

onMounted(() => {
  if (!root.value) {
    return
  }

  root.value.popover = 'manual'
})

watch(hasLoadingToast, pending =>
  pending
    ? window.addEventListener('beforeunload', warnBeforeLeaving)
    : window.removeEventListener('beforeunload', warnBeforeLeaving),
)

onBeforeUnmount(() => window.removeEventListener('beforeunload', warnBeforeLeaving))

defineExpose({ info, success, warning, error, loading, promise, update, dismiss })
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
.popover,
.popover:popover-open {
  inset: unset;
  @apply fixed top-3 right-3 p-0 bg-transparent m-0 overflow-visible border-0 backdrop:bg-transparent;
}

.toast-enter-active {
  @apply opacity-100 transition-all duration-200 ease-in;
}

.toast-leave-active {
  @apply opacity-0 transition-all duration-200 ease-out;
}

.toast-enter-from,
.toast-leave-to {
  @apply opacity-0;
  transform: translateX(100px);
}
</style>
