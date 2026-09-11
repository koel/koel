# Frontend Conventions

## Lucide Icons

- When importing icons from `lucide-vue-next`, always use the `Icon` suffix (e.g. `SparklesIcon`, not `Sparkles`; `SearchIcon`, not `Search`).

## TypeScript Conventions

- Always prefer generics over type casting when the API supports it (e.g. `container.querySelector<HTMLElement>('.foo')` instead of `container.querySelector('.foo') as HTMLElement`).
- Do not add explicit return types when they can be inferred by the compiler. Only annotate return types when inference is insufficient or ambiguous.
- When using `setTimeout`, `setInterval`, or `requestAnimationFrame`, always ensure they are cleaned up: on component unmount (`onBeforeUnmount`), on state transitions that invalidate them (e.g. drop cancels a pending expand), and when the operation completes. Treat every timer/rAF as a resource that must be explicitly released.

## Vue Template Conventions

- Always use Vue's same-name shorthand for bindings: `:foo` instead of `:foo="foo"`. This applies to props, components, and any v-bind where the attribute name matches the variable name.

## Vue Forms

- Any Vue surface that takes user input and commits it on submit must use the `useForm` composable from `@/composables/useForm` — including inline composers, popovers, and mini name-prompts that aren't named `*Form.vue`. Don't roll your own `ref<string>('')` + manual submit handling.
- Pair it with the canonical wiring: `<form @submit.prevent="handleSubmit" @keydown.esc="maybeClose">`, inputs use `v-koel-focus` (not manual `onMounted` focus) and `required` (not manual `:disabled`), Save is `<Btn type="submit">`, Cancel is `<Btn type="button" @click.prevent="maybeClose">`, and `maybeClose` does `if (isPristine() || (await showConfirmDialog(...))) emit('cancel')`.
- For purely-local submits (no server call), pass `useOverlay: false` and have `onSubmit` just emit. Use the optional `validator` callback for non-HTML5 rules (e.g. trim/whitespace).
- Read `resources/assets/js/components/playlist/CreatePlaylistFolderForm.vue` before writing a new form — that's the reference shape.

## Vue Component Decomposition

- Always try to break Vue components into smaller, self-managed-state subcomponents. A component that hosts multiple stages, multiple modes, or multiple distinct UI shapes should split each into its own focused child. The parent becomes a thin orchestrator (state machine + API calls + composition); each child owns one shape with clear props in and events out, no service dependencies of its own, and is testable in isolation with minimal mocks. Reference shape: `TwoFactorAuthSettings.vue` (orchestrator) → `TwoFactorEnrollment.vue` / `TwoFactorRecoveryCodes.vue` / `TwoFactorManageActions.vue` (focused children).

## Vue Component Styling

- Put shared/base Tailwind classes directly on the HTML element via the `class` attribute.
- For variant-specific styles (e.g. modes, states), use custom CSS classes (`.initial`, `.chat`, `.user`, `.error`, etc.) with `@apply` in a scoped `<style>` block.
- Do NOT build class strings in JavaScript arrays or computed properties.

## Frontend Testing

- Prefer semantic queries (`getByRole`, `getByLabelText`, `getByText`) via `screen` from `@testing-library/vue`. Use `data-testid` only as a last resort when no semantic query is available.
- `getBy*` queries already throw if the element is not found, so never wrap them in `expect().toBeTruthy()`. Just call `screen.getByTestId('foo')` directly — the throw is the assertion. Use `expect(screen.queryBy*()).toBeNull()` to assert absence.
