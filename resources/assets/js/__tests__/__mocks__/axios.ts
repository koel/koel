export default {
  get: jest.fn((): Promise<void> => Promise.resolve()),
  post: jest.fn((): Promise<void> => Promise.resolve()),
  patch: jest.fn((): Promise<void> => Promise.resolve()),
  put: jest.fn((): Promise<void> => Promise.resolve()),
  delete: jest.fn((): Promise<void> => Promise.resolve()),
  request: jest.fn(() => Promise.resolve({ data: [] }))
}
