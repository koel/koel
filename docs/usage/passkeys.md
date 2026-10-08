---
description: Log in to Koel with a passkey — your fingerprint, face, screen lock, or a security key — instead of a password.
---

# Passkeys

A passkey lets you log in with your fingerprint, face, screen lock, or a security key instead of typing your password. Passkeys can't be phished or reused on another site, so they're also safer.

## Add a Passkey

1. Go to **Settings**.
2. Open the **Security** section.
3. Under **Passkeys**, click **Add a Passkey**.
4. Give it a name you'll recognize later, like "MacBook" or "YubiKey", and click **Add**.
5. Follow your browser's prompt.

You can add as many passkeys as you like, for example one per device.

## Log In With a Passkey

On the login screen, click the fingerprint button (**Log in with a passkey**) and follow your browser's prompt. You don't need to enter your email or password, and Koel doesn't ask for a two-factor code either: the passkey already proves it's you.

Your password keeps working, so you can still log in with it.

## Remove a Passkey

Open the **Security** section, find the passkey under **Passkeys**, and click **Remove**. It stops working right away.

## For Admins

Passkeys are tied to the domain in your `APP_URL`. If you move Koel to a different domain, passkeys added before the move stop working, and users need to add new ones. If passkeys don't work at all, see [Troubleshooting](../troubleshooting).
