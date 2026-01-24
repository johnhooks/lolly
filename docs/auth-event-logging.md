# Authentication Event Logging

Lolly can log WordPress authentication events for security monitoring and access auditing. This feature is controlled by the **Authentication Logging** toggle in the settings page, with individual event types configurable in the **Authentication Events** card.

## Events Captured

### User Logged In

Logged when a user successfully authenticates.

**Hook:** `wp_login`

**Logged data:**
- Target user ID
- Actor (same as target for self-login)

### User Logged Out

Logged when a user ends their session.

**Hook:** `wp_logout`

**Logged data:**
- Target user ID
- Actor (same as target for self-logout)

### Login Failed

Logged when a login attempt fails. **Disabled by default** due to potential high volume on sites under attack.

**Hook:** `wp_login_failed`

**Log level:** `warning`

**Logged data:**
- Username attempted
- WP_Error with failure reason

### Password Reset

Logged when a user's password is reset via the password reset flow.

**Hook:** `after_password_reset`

**Logged data:**
- Target user ID
- Actor (admin who initiated reset, if applicable)

### Password Changed

Logged when a user's password is changed via profile update.

**Hook:** `profile_update`

**Logged data:**
- Target user ID
- Actor (admin who made the change, if different from target)

### Application Password Created

Logged when an application password is generated for a user.

**Hook:** `wp_create_application_password`

**Logged data:**
- Target user ID
- Application name
- Application UUID
- Actor (admin who created it, if different from target)

Note: The actual password value is never logged.

### Application Password Deleted

Logged when an application password is revoked.

**Hook:** `wp_delete_application_password`

**Logged data:**
- Target user ID
- Application name
- Application UUID
- Actor (admin who deleted it, if different from target)

## Log Format

Events are logged at `info` level (except login failures which use `warning`) with ECS-compatible formatting:

```json
{
  "@timestamp": "2026-01-23T10:00:00.000Z",
  "message": "User logged in.",
  "log.level": "info",
  "user": { "id": 5 },
  "ctxt_target_user": { "id": 5 }
}
```

The `user` field contains the actor (who performed the action). The `target_user` field contains the affected user.

## Configuration

Enable or disable via **Settings > Lolly Log > Authentication Logging**.

When enabled, the **Authentication Events** card appears with toggles for each event type:

| Event | Default |
|-------|---------|
| Login | Enabled |
| Logout | Enabled |
| Login Failed | Disabled |
| Password Changed | Enabled |
| Application Password Created | Enabled |
| Application Password Deleted | Enabled |

Login failures are disabled by default because sites under brute-force attack can generate extremely high log volume. Consider your log storage capacity before enabling.
