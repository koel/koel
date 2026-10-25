export const makeReverbConnectionOptions = (server: ReverbServer) => ({
  wsHost: server.host,
  wsPort: server.port,
  wssPort: server.port,
  forceTLS: server.tls,
  enabledTransports: ['ws', 'wss'] as ('ws' | 'wss')[],
})
