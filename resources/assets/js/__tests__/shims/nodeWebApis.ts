// The VM test context gets jsdom's Crypto, which has no SubtleCrypto, and no web streams.
// Hand it Node's implementations, which are also what Node's Response bodies are made of.
import { webcrypto } from 'node:crypto'
import { ReadableStream, TransformStream, WritableStream } from 'node:stream/web'

Object.entries({ crypto: webcrypto, ReadableStream, TransformStream, WritableStream }).forEach(([name, value]) =>
  Object.defineProperty(window, name, { configurable: true, value }),
)
