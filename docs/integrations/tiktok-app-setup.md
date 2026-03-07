# TikTok Developer App Setup for Okacrm

This guide documents the TikTok developer configuration required before implementing TikTok OAuth and sync features in Okacrm.

## RFC Reference
- RFC `074` - TikTok Developer App Setup Documentation

## Prerequisites
- TikTok for Developers account with access to [TikTok Developer Center](https://developers.tiktok.com/)
- Access to environment domains used by Okacrm (local/staging/production)
- Team process for storing secrets in a server-side secret manager

## 1. Create and Separate TikTok Apps by Environment
TikTok supports app sandboxes. Keep one app for lower environments and one app for production.

1. In [Developer Center](https://developers.tiktok.com/), create a new app for Okacrm.
2. Add `Login Kit` and complete the required app metadata.
3. Create a sandbox for development/testing from the app dashboard.
4. Keep environment usage strict:

| Okacrm Environment | TikTok App Mode | Purpose |
| --- | --- | --- |
| Local + Staging | Sandbox app | Integration development, QA, and non-production token tests |
| Production | Production app | Real creator account linking and live sync |

Do not share production credentials in local/staging environments.

## 2. Configure Redirect URIs
Configure redirect URIs in `Login Kit` settings for each app mode.

### Sandbox App Redirect URIs
- `https://influence-me.test/auth/tiktok/callback`
- `https://staging.yourdomain.com/auth/tiktok/callback` (if staging is used)

### Production App Redirect URIs
- `https://yourdomain.com/auth/tiktok/callback`

Redirect URI rules to enforce:
- Redirect URI in code must exactly match a URI registered in TikTok.
- Keep URIs static (no query strings or fragments).
- TikTok allows up to 10 redirect URIs per app.
- Use HTTPS for non-local web environments.

## 3. Required OAuth Scopes
Request the following scopes for MVP account linking and read-only sync:

- `user.info.basic`: creator identifier and basic account data
- `user.info.profile`: profile metadata used in account records
- `user.info.stats`: follower and profile-level metrics used for analytics snapshots
- `video.list`: creator video list and video-level fields used for content sync

As of February 19, 2026, TikTok's public scopes reference does not list a separate `video.insights` scope. For read-only insights, use `user.info.stats` plus `video.list` data fields.

## 4. Environment Variables and Consumption
Define these variables in `.env` (local) and secret-managed environment config (staging/production):

```env
TIKTOK_CLIENT_ID=your_tiktok_client_id
TIKTOK_CLIENT_SECRET=your_tiktok_client_secret
TIKTOK_REDIRECT_URI=https://influence-me.test/auth/tiktok/callback
```

Where they are consumed in the RFC roadmap:
- `TIKTOK_CLIENT_ID`, `TIKTOK_CLIENT_SECRET`, `TIKTOK_REDIRECT_URI`: `config/services.php` in RFC `075`
- TikTok OAuth scopes are hardcoded in `App\Enums\SocialNetwork::oauthScopes()` for read-only account details/stats
- Access and refresh tokens returned by TikTok OAuth: TikTok account persistence and sync workflows (RFC `076`, `081`, `086`)

## 5. Security Notes
- Never commit TikTok client secrets or tokens to source control.
- Store `TIKTOK_CLIENT_SECRET` only in server-side secret managers (not browser-visible config).
- Keep OAuth token exchange on backend only; frontend must never handle client secret.
- Persist access/refresh tokens encrypted at rest when TikTok account storage is implemented.
- Rotate client secret immediately if leaked; rotate proactively on a fixed schedule (for example quarterly).
- Log token refresh and OAuth failures without logging raw token values.

## 6. App Review and Go-Live Checklist
Before moving production TikTok auth live:

- App information is complete and policy URLs are valid.
- Production redirect URI is registered and verified.
- Required scopes are enabled and approved for production usage.
- OAuth flow tested in sandbox end-to-end (redirect, callback, token exchange).
- OAuth flow tested in production with a controlled pilot account.
- Monitoring is in place for OAuth failures and token refresh failures.
- Runbook exists for token invalidation, secret rotation, and reconnect support.

## 7. Troubleshooting

### `redirect_uri` mismatch
- Confirm `TIKTOK_REDIRECT_URI` exactly matches a registered URI in TikTok Developer Center.
- Confirm callback route path is `/auth/tiktok/callback` in all environments.

### `insufficient_scope`
- Confirm OAuth redirect requests read-only scopes: `user.info.basic`, `user.info.profile`, `user.info.stats`.
- Confirm production app review approved the required scopes.

### Sandbox login succeeds but production fails
- Verify the correct app credentials are loaded for the target environment.
- Confirm production app status/review state in Developer Center.

## Official References
- [TikTok Login Kit for Web](https://developers.tiktok.com/doc/login-kit-web)
- [TikTok Scopes Reference](https://developers.tiktok.com/doc/tiktok-api-scopes)
- [TikTok Add Sandbox Guide](https://developers.tiktok.com/doc/get-started-add-sandbox)
- [TikTok Login Kit Manage App Access Tokens](https://developers.tiktok.com/doc/manage-user-access-token)
