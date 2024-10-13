import { TypedEmitter } from 'tiny-typed-emitter'
import type { Events } from '@/config'

const eventBus = new TypedEmitter<Events>()
eventBus.setMaxListeners(100)

export { eventBus }
