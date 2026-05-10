/**
 * The equalizer dropdown uses composite string keys to disambiguate
 * built-in presets (looked up by name) from custom user presets
 * (looked up by id). These helpers wrap the encoding/decoding so the
 * prefix literals don't leak into call sites.
 */
const CUSTOM_PREFIX = 'custom:'
const BUILTIN_PREFIX = 'builtin:'

export const customKey = (id: string) => `${CUSTOM_PREFIX}${id}`

export const builtInKey = (name: string) => `${BUILTIN_PREFIX}${name}`

export const isCustomKey = (key: string | null): key is string => key !== null && key.startsWith(CUSTOM_PREFIX)

export const isBuiltInKey = (key: string | null): key is string => key !== null && key.startsWith(BUILTIN_PREFIX)

export const customIdFromKey = (key: string) => key.slice(CUSTOM_PREFIX.length)

export const builtInNameFromKey = (key: string) => key.slice(BUILTIN_PREFIX.length)
