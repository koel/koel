<template>
  <div
    class="rich-text-editor rounded-sm text-k-fg-input bg-k-bg-input border border-k-fg-10 focus-within:border-k-fg-30"
  >
    <div v-if="editor" class="flex flex-wrap items-center gap-0.5 border-b border-k-fg-10 px-1.5 py-1" role="toolbar">
      <button
        v-for="action in actions"
        :key="action.label"
        :aria-pressed="action.isActive()"
        :disabled="action.isDisabled?.()"
        :title="action.label"
        class="p-1.5 rounded-sm text-k-fg-70 hover:text-k-fg aria-pressed:text-k-highlight aria-pressed:bg-k-fg-10 disabled:opacity-30 disabled:pointer-events-none"
        type="button"
        @click="action.run"
        @mousedown.prevent
      >
        <component :is="action.icon" :size="16" />
        <span class="sr-only">{{ action.label }}</span>
      </button>
    </div>

    <EditorContent :editor class="content px-4 py-2.5 h-48 overflow-y-auto" />

    <div
      ref="linkPanel"
      class="fixed inset-auto m-0 p-2 rounded-md border border-k-fg-10 bg-k-bg shadow-lg"
      popover="auto"
      style="left: -9999px; top: -9999px"
      @keydown.esc.stop.prevent="closeLinkPanel"
      @toggle="onLinkPanelToggle"
    >
      <div class="flex items-center gap-2 w-96 max-w-[90vw]">
        <TextInput
          ref="linkInput"
          v-model="linkUrl"
          class="flex-1 h-8 py-0! px-2.5!"
          name="link-url"
          placeholder="https://…"
          type="url"
          @keydown.enter.prevent="applyLink"
        />
        <Btn bordered class="h-8 px-1.5! py-0!" title="Apply" type="button" variant="ghost" @click="applyLink">
          <CheckIcon :size="14" />
          <span class="sr-only">Apply</span>
        </Btn>
        <Btn
          v-if="editingExistingLink"
          class="h-8 px-1.5! py-0!"
          title="Remove link"
          type="button"
          variant="ghost"
          @click="removeLink"
        >
          <UnlinkIcon :size="14" />
          <span class="sr-only">Remove link</span>
        </Btn>
      </div>
    </div>
  </div>
</template>

<script lang="ts" setup>
import StarterKit from '@tiptap/starter-kit'
import { autoUpdate, computePosition, flip, offset, shift } from '@floating-ui/dom'
import { EditorContent, posToDOMRect, useEditor } from '@tiptap/vue-3'
import {
  BoldIcon,
  CheckIcon,
  Heading2Icon,
  Heading3Icon,
  ItalicIcon,
  LinkIcon,
  ListIcon,
  ListOrderedIcon,
  StrikethroughIcon,
  UnderlineIcon,
  UnlinkIcon,
} from 'lucide-vue-next'
import { nextTick, onBeforeUnmount, ref, watch } from 'vue'

import Btn from '@/components/ui/form/Btn.vue'
import TextInput from '@/components/ui/form/TextInput.vue'

const html = defineModel<string>({ default: '' })

const linkPanel = ref<HTMLElement>()
const linkInput = ref<InstanceType<typeof TextInput>>()
const linkUrl = ref('')
const hasBeenFocused = ref(false)
const editingExistingLink = ref(false)

let stopPositioningLinkPanel: (() => void) | null = null

const editor = useEditor({
  content: html.value,
  extensions: [
    StarterKit.configure({
      blockquote: false,
      code: false,
      codeBlock: false,
      horizontalRule: false,
      heading: { levels: [2, 3] },
      link: { openOnClick: false },
    }),
  ],
  editorProps: { attributes: { class: 'rich-text' } },
  onFocus: () => (hasBeenFocused.value = true),
  onUpdate: ({ editor: instance }) => (html.value = instance.isEmpty ? '' : instance.getHTML()),
})

watch(html, value => {
  if (editor.value && value !== editor.value.getHTML() && !(value === '' && editor.value.isEmpty)) {
    editor.value.commands.setContent(value, { emitUpdate: false })
  }
})

const chain = () => editor.value!.chain().focus()
const isActive = (name: string, attributes?: Record<string, unknown>) =>
  Boolean(hasBeenFocused.value && editor.value?.isActive(name, attributes))

const canEditLink = () =>
  Boolean(editor.value && (!editor.value.state.selection.empty || editor.value.isActive('link')))

const positionLinkPanel = async () => {
  const instance = editor.value

  if (!instance || !linkPanel.value) {
    return
  }

  const { from, to } = instance.state.selection
  const selectedText = {
    getBoundingClientRect: () => posToDOMRect(instance.view, from, to),
    contextElement: instance.view.dom,
  }

  const { x, y } = await computePosition(selectedText, linkPanel.value, {
    placement: 'bottom-start',
    middleware: [offset(6), flip(), shift({ padding: 8 })],
    strategy: 'fixed',
  })

  linkPanel.value.style.left = `${x}px`
  linkPanel.value.style.top = `${y}px`
}

const startEditingLink = () => {
  chain().extendMarkRange('link').run()

  editingExistingLink.value = editor.value!.isActive('link')
  linkUrl.value = editor.value!.getAttributes('link').href ?? ''
  linkPanel.value?.showPopover()
}

const closeLinkPanel = () => {
  linkPanel.value?.hidePopover()
  editor.value?.commands.focus()
}

const onLinkPanelToggle = async (event: Event) => {
  stopPositioningLinkPanel?.()
  stopPositioningLinkPanel = null

  if ((event as ToggleEvent).newState !== 'open') {
    return
  }

  const instance = editor.value!

  stopPositioningLinkPanel = autoUpdate(
    { getBoundingClientRect: () => instance.view.dom.getBoundingClientRect(), contextElement: instance.view.dom },
    linkPanel.value!,
    positionLinkPanel,
  )

  await nextTick()
  linkInput.value?.el?.focus()
}

const applyLink = () => {
  const href = linkUrl.value.trim()

  if (href) {
    chain().extendMarkRange('link').setLink({ href }).run()
  } else {
    chain().extendMarkRange('link').unsetLink().run()
  }

  closeLinkPanel()
}

const removeLink = () => {
  chain().extendMarkRange('link').unsetLink().run()
  closeLinkPanel()
}

const actions = [
  { label: 'Bold', icon: BoldIcon, isActive: () => isActive('bold'), run: () => chain().toggleBold().run() },
  { label: 'Italic', icon: ItalicIcon, isActive: () => isActive('italic'), run: () => chain().toggleItalic().run() },
  {
    label: 'Underline',
    icon: UnderlineIcon,
    isActive: () => isActive('underline'),
    run: () => chain().toggleUnderline().run(),
  },
  {
    label: 'Strikethrough',
    icon: StrikethroughIcon,
    isActive: () => isActive('strike'),
    run: () => chain().toggleStrike().run(),
  },
  {
    label: 'Heading',
    icon: Heading2Icon,
    isActive: () => isActive('heading', { level: 2 }),
    run: () => chain().toggleHeading({ level: 2 }).run(),
  },
  {
    label: 'Subheading',
    icon: Heading3Icon,
    isActive: () => isActive('heading', { level: 3 }),
    run: () => chain().toggleHeading({ level: 3 }).run(),
  },
  {
    label: 'Bulleted list',
    icon: ListIcon,
    isActive: () => isActive('bulletList'),
    run: () => chain().toggleBulletList().run(),
  },
  {
    label: 'Numbered list',
    icon: ListOrderedIcon,
    isActive: () => isActive('orderedList'),
    run: () => chain().toggleOrderedList().run(),
  },
  {
    label: 'Link',
    icon: LinkIcon,
    isActive: () => isActive('link'),
    isDisabled: () => !canEditLink(),
    run: startEditingLink,
  },
]

onBeforeUnmount(() => {
  stopPositioningLinkPanel?.()
  editor.value?.destroy()
})
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
.content :deep(.ProseMirror) {
  @apply min-h-full outline-hidden;
}
</style>
