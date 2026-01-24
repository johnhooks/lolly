# User Event Logging

Lolly can log WordPress user lifecycle events for auditing purposes. This feature is controlled by the **User Event Logging** toggle in the settings page.

## Events Captured

### User Created

Logged when a new user account is created via `wp_insert_user()` or the admin dashboard.

**Hook:** `user_register`

**Logged data:**
- Target user (the new account)
- Assigned roles
- Actor (admin who created the account, if applicable)

### User Deleted

Logged when a user account is deleted via `wp_delete_user()` or the admin dashboard.

**Hook:** `delete_user`

**Logged data:**
- Target user (the deleted account)
- User's roles at time of deletion
- Reassignment user (if content was reassigned to another user)
- Actor (admin who deleted the account)

### User Role Changed

Logged when a user's role is replaced via `WP_User::set_role()` or the admin dashboard.

**Hook:** `set_user_role`

**Logged data:**
- Target user (whose role changed)
- New role
- Previous roles
- Actor (admin who made the change, if different from target)

### User Role Added

Logged when an additional role is added to a user via `WP_User::add_role()`.

**Hook:** `add_user_role`

**Logged data:**
- Target user
- Added role
- Actor (admin who made the change, if different from target)

### User Role Removed

Logged when a role is removed from a user via `WP_User::remove_role()`.

**Hook:** `remove_user_role`

**Logged data:**
- Target user
- Removed role
- Actor (admin who made the change, if different from target)

## Log Format

Events are logged at `info` level with ECS-compatible formatting:

```json
{
  "@timestamp": "2026-01-23T10:00:00.000Z",
  "message": "User created.",
  "log.level": "info",
  "user": { "id": 1 },
  "ctxt_target_user": { "id": 5 },
  "ctxt_roles": ["subscriber"]
}
```

The `user` field contains the actor (who performed the action). The `target_user` field contains the affected user.

## Configuration

Enable or disable via **Settings > Lolly Log > User Event Logging**.

When disabled, no user lifecycle events are logged. Other logging features (REST API, HTTP Client) are unaffected.
