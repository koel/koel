---
description: Configuring reverse proxy authentication via headers and IP allow-lists for Koel Plus.
---

# Proxy Authentication <sup>Beta</sup>

Koel can be configured to authenticate users via a reverse proxy.
Proxy authentication is useful in environments where users are already authenticated by a proxy server.

:::warning Beta Feature
This feature is currently in beta. Expect bugs and rough edges.
:::

:::danger Caution
Proxy authentication bypasses Koel's built-in authentication system and relies on the proxy server to authenticate users.
Make sure your proxy server is secure and properly configured.
:::

To enable proxy authentication, set `PROXY_AUTH_ENABLED` in `.env` to `true` and provide the necessary configuration:

* `PROXY_AUTH_USER_HEADER`: The header name that contains the unique identifier for the user, e.g., `remote-user`.
* `PROXY_AUTH_PREFERRED_NAME_HEADER`: The header name that contains the user's preferred, humanly-readable name, e.g., `remote-preferred-name`.
* `PROXY_AUTH_ALLOW_LIST`: A comma-separated list of allowed proxy IPs or Classless Inter-Domain Routing (CIDRs), e.g., `10.10.1.0/24` or `2001:0db8:/32`. If this value is empty, NO requests will be allowed (which essentially means proxy authentication is disabled).

Now when a request comes in, Koel will look for specific headers to determine the user's identity.
If the headers are found, Koel will attempt to log the user in automatically using the unique identifier.
If the user is not found, Koel will create a new user with the unique identifier and the preferred name.

## Troubleshooting

If proxy authentication isn't logging users in (you keep landing on Koel's password login screen despite a successful upstream auth handshake), check `storage/logs/laravel.log` for `[ProxyAuth]` entries. Koel logs a structured warning for each of the three failure modes:

* **`Remote address not in allow list`** — the request reached Koel from an IP not covered by `PROXY_AUTH_ALLOW_LIST`. The log line includes both the observed `remote_addr` and the configured allow list. If your proxy runs in a Docker network or behind another layer, the address Koel sees may not be the one you expect — adjust the allow list or your `TrustProxies` config accordingly.
* **`User header not present on request`** — Koel got the request but the configured `PROXY_AUTH_USER_HEADER` was missing. This usually means the proxy isn't propagating the header to the upstream request. Caddy users: double-check the `copy_headers` directive in your `forward_auth` block. Traefik / nginx users: confirm the header is in the forward-auth response and isn't being stripped before the upstream call.
* **`Failed to create or update user from SSO headers`** — Koel received the header but failed to provision the user. The log entry includes the full exception; common causes are organization-membership constraints or invalid email derivation from the identifier.

<style>
sup {
  font-size: 0.8rem;
  text-transform: uppercase;
  opacity: .8;
}
</style>
