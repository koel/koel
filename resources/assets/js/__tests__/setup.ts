import vueSnapshotSerializer from 'jest-serializer-vue'
import { expect } from 'vitest'

expect.addSnapshotSerializer(vueSnapshotSerializer)
