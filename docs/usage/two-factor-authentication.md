---
description: Set up two-factor authentication on your Koel account to add a one-time code to every login.
---

# Two-Factor Authentication

::: warning Mobile app compatibility
Older versions of Koel Player may not support two-factor authentication. Update to the latest version before enabling 2FA on an account you sign in to from mobile.
:::

Two-factor authentication (2FA) adds a second step to logging in. After your email and password, Koel asks for a one-time code from your authenticator app. Without that code (or one of your recovery codes), nobody can sign in to your account — even with your password.

## Enable Two-Factor Authentication

1. Go to your profile.
2. Switch to the **Security** tab.
3. Click **Enable Two-Factor Authentication**.
4. Scan the QR code with your authenticator app (1Password, Authy, Google Authenticator, Microsoft Authenticator, etc.).
5. Enter the 6-digit code your app shows you and click **Confirm**.
6. Save the recovery codes Koel reveals next.

## Save Your Recovery Codes

When you enable 2FA, Koel shows you eight recovery codes. **Save them somewhere safe** — a password manager, a printout in a drawer, an encrypted note. You won't be able to see them again.

Each recovery code can be used once if you ever lose access to your authenticator app. After a code is used, it disappears.

If you run out (or just want fresh ones), open the **Security** tab and click **Regenerate Recovery Codes**. You'll need to confirm with a code from your authenticator app or one of your existing recovery codes. The old codes stop working as soon as new ones are generated.

## Logging In With Two-Factor Authentication

1. Enter your email and password as usual.
2. Koel asks for a code.
3. Enter the 6-digit code from your authenticator app — or a recovery code if you don't have your phone.
4. Click **Verify**.

You're in.

## Disable Two-Factor Authentication

1. Go to the **Security** tab.
2. Click **Disable**.
3. Confirm, then enter a code from your authenticator app or a recovery code.

Your account is back to email-and-password only. The 2FA secret and recovery codes are cleared — re-enabling later starts the setup from scratch.
