<template>
  <div
    class="message"
    :class="message.type"
    @click="dismiss"
    title="Click to dismiss"
  >
    <aside>
      <icon :icon="typeIcon" class="icon"/>
      <icon :icon="faTimesCircle" class="icon-dismiss"/>
    </aside>
    <main>{{ message.content }}</main>
  </div>
</template>

<script lang="ts" setup>
import { computed, onMounted, toRefs } from 'vue'
import {
  faCircleCheck,
  faCircleExclamation,
  faCircleInfo,
  faTimesCircle,
  faTriangleExclamation
} from '@fortawesome/free-solid-svg-icons'

const props = defineProps<{ message: ToastMessage }>()
const { message } = toRefs(props)

const typeIcon = computed(() => {
  switch (message.value.type) {
    case 'info':
      return faCircleInfo
    case 'success':
      return faCircleCheck
    case 'warning':
      return faTriangleExclamation
    case 'danger':
      return faCircleExclamation
  }
})

let timeoutHandler: number

const emit = defineEmits<{ (e: 'dismiss', message: ToastMessage): void }>()

const dismiss = () => {
  emit('dismiss', message.value)
  window.clearTimeout(timeoutHandler)
}

onMounted(() => {
  timeoutHandler = window.setTimeout(() => dismiss(), message.value.timeout * 1000)
})
</script>

<style lang="scss" scoped>
.message {
  border-radius: 4px 0 0 4px;
  cursor: pointer;
  display: flex;
  align-items: stretch;
  opacity: .9;
  transition: transform .3s;

  .icon-dismiss {
    display: none;
  }

  &:hover {
    opacity: 1;
    transform: scale(1.1);
    transform-origin: right;

    .icon {
      display: none;
    }

    .icon-dismiss {
      display: block;
    }
  }
}

aside {
  display: flex;
  align-items: center;
  padding: 0 8px;
  background: rgba(0, 0, 0, .1)
}

main {
  flex: 1;
  padding: 7px 14px 7px 10px;
}

.info {
  background-color: rgb(12 74 110);
  color: rgb(224 242 254);
}

.danger {
  background-color: rgb(185 28 28);
  color: rgb(254 226 226);
}

.success {
  background-color: rgb(5 150 105);
  color: rgb(209 250 229);
}

.warning {
  background-color: rgb(249 115 22);
  color: rgb(255 237 213);
}
</style>
