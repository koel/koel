import { describe, expect, it, vi } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { defineComponent, ref } from 'vue'
import MessageToaster from './MessageToaster.vue'

describe('messageToaster', () => {
  const h = createHarness()

  const renderToaster = () => {
    const toaster = ref<InstanceType<typeof MessageToaster>>()

    h.render(
      defineComponent({
        components: { MessageToaster },
        setup: () => ({ toaster }),
        template: '<MessageToaster ref="toaster" />',
      }),
    )

    return toaster.value!
  }

  const deferred = <T>() => {
    let resolve!: (value: T) => void
    let reject!: (reason: unknown) => void

    const promise = new Promise<T>((resolvePromise, rejectPromise) => {
      resolve = resolvePromise
      reject = rejectPromise
    })

    return {
      promise,
      resolve,
      reject,
    }
  }

  const getToast = () => screen.getByRole('article')

  it('has no messages initially', () => {
    renderToaster()

    expect(screen.queryByRole('article')).toBeNull()
  })

  it('announces every new message to screen readers, even when two arrive at once', async () => {
    const toaster = renderToaster()

    toaster.success('Saved')
    toaster.info('Syncing')
    await h.tick()

    const announcements = Array.from(screen.getByTestId('toast-announcements').children).map(line => line.textContent)
    expect(announcements).toEqual(['Saved', 'Syncing'])
  })

  it('updates a toast in place', async () => {
    const toaster = renderToaster()

    const id = toaster.info('Syncing')
    await h.tick()
    toaster.update(id, {
      type: 'danger',
      content: 'Sync failed',
    })
    await h.tick()

    expect(screen.getAllByRole('article')).toHaveLength(1)
    expect(getToast().classList).toContain('danger')
    expect(getToast().textContent).toContain('Sync failed')
  })

  it('renders a component with its props in place of the text', async () => {
    const toaster = renderToaster()
    const Content = defineComponent({ props: { count: Number }, template: '<b data-testid="content">{{ count }}</b>' })

    toaster.info({
      content: 'Counting',
      component: Content,
      props: { count: 3 },
    })
    await h.tick()

    expect(screen.getByTestId('content').textContent).toBe('3')
  })

  it('runs the handler of an action', async () => {
    const toaster = renderToaster()
    const undo = vi.fn()

    toaster.success({
      content: 'Removed',
      actions: [
        {
          label: 'Undo',
          handler: undo,
        },
      ],
    })
    await h.tick()
    await h.user.click(screen.getByRole('button', { name: 'Undo' }))

    expect(undo).toHaveBeenCalled()
  })

  it('calls back when a toast is dismissed', async () => {
    const toaster = renderToaster()
    const onDismiss = vi.fn()

    const id = toaster.success({
      content: 'Saved',
      onDismiss,
    })
    toaster.dismiss(id)
    await h.tick()

    expect(onDismiss).toHaveBeenCalled()
    expect(screen.queryByRole('article')).toBeNull()
  })

  it('cannot be dismissed while its promise is pending', async () => {
    const toaster = renderToaster()

    toaster.promise(deferred().promise, {
      loading: 'Saving',
      success: 'Saved',
      error: 'Failed',
    })
    await h.tick()

    expect(getToast().classList).toContain('loading')
    expect(screen.queryByRole('button', { name: 'Dismiss' })).toBeNull()
  })

  it('turns into a success toast built from the resolved value', async () => {
    const toaster = renderToaster()
    const { promise, resolve } = deferred<number>()

    toaster.promise(promise, {
      loading: 'Saving',
      success: count => `${count} saved`,
      error: 'Failed',
    })
    resolve(3)
    await h.tick(2)

    expect(getToast().classList).toContain('success')
    expect(getToast().textContent).toContain('3 saved')
    screen.getByRole('button', { name: 'Dismiss' })
  })

  it('turns into a warning when the resolved value maps to one', async () => {
    const toaster = renderToaster()
    const { promise, resolve } = deferred<number>()

    toaster.promise(promise, {
      loading: 'Saving',
      success: 'Saved',
      error: 'Failed',
      warning: count => (count ? undefined : 'Nothing saved'),
    })
    resolve(0)
    await h.tick(2)

    expect(getToast().classList).toContain('warning')
    expect(getToast().textContent).toContain('Nothing saved')
  })

  it('turns into an error toast built from the rejection', async () => {
    const toaster = renderToaster()
    const { promise, reject } = deferred()

    toaster.promise(promise, {
      loading: 'Saving',
      success: 'Saved',
      error: error => (error instanceof Error ? error.message : ''),
    })
    reject(new Error('Disk full'))
    await h.tick(2)

    expect(getToast().classList).toContain('danger')
    expect(getToast().textContent).toContain('Disk full')
  })

  it('drops the loading content and actions once settled', async () => {
    const toaster = renderToaster()
    const { promise, resolve } = deferred()
    const Content = defineComponent({ template: '<b data-testid="content" />' })

    toaster.promise(promise, {
      loading: {
        content: 'Saving',
        component: Content,
        actions: [
          {
            label: 'Stop',
            handler: vi.fn(),
          },
        ],
      },
      success: 'Saved',
      error: 'Failed',
    })
    await h.tick()
    resolve(undefined)
    await h.tick(2)

    expect(screen.queryByTestId('content')).toBeNull()
    expect(screen.queryByRole('button', { name: 'Stop' })).toBeNull()
  })

  it('warns before leaving the page only while a toast is loading', async () => {
    const toaster = renderToaster()
    const { promise, resolve } = deferred()

    const leavingIsBlocked = () => !window.dispatchEvent(new Event('beforeunload', { cancelable: true }))

    toaster.success('Saved')
    await h.tick()
    expect(leavingIsBlocked()).toBe(false)

    toaster.promise(promise, {
      loading: 'Saving',
      success: 'Saved',
      error: 'Failed',
    })
    await h.tick()
    expect(leavingIsBlocked()).toBe(true)

    resolve(undefined)
    await h.tick(2)
    expect(leavingIsBlocked()).toBe(false)
  })
})
