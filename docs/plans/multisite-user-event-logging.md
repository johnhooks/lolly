# Plan: Multisite User Event Logging

## Problem Statement

In WordPress multisite installations, users can be added to or removed from individual sites without being created or deleted at the network level. The current user event logging only captures network-level user lifecycle events, missing site-level membership changes.

## Why This Matters

On a multisite network:

- A user can exist on the network but not have access to a specific site
- Administrators can grant or revoke site access without deleting the user
- These membership changes are security-relevant events that should be auditable

Without logging these events, administrators have no visibility into who was granted or revoked access to specific sites.

## Proposed Events

| Event | Hook | Description |
|-------|------|-------------|
| User added to site | `add_user_to_blog` | User granted access to a site |
| User removed from site | `remove_user_from_blog` | User's access to a site revoked |

### Hook Signatures

```php
do_action('add_user_to_blog', int $user_id, string $role, int $blog_id);
do_action('remove_user_from_blog', int $user_id, int $blog_id, int $reassign);
```

## Logged Data

**User added to site:**
- Target user
- Site ID / site name
- Role assigned on that site
- Actor (admin who granted access)

**User removed from site:**
- Target user
- Site ID / site name
- Reassignment user (if content was reassigned)
- Actor (admin who revoked access)

## Configuration

These listeners should only be active when:
1. WordPress is running in multisite mode (`is_multisite()`)
2. User event logging is enabled

No additional configuration setting needed - multisite events are a natural extension of user event logging.

## Testing

**Research needed:** Determine how to test multisite functionality with our current setup.

Options to investigate:
- WpBrowser's `multisite: true` configuration in WPLoader
- Separate test suite for multisite (e.g., `WpunitMultisite.suite.yml`)
- Whether slic supports multisite WordPress instances
- Codeception environments for toggling single site vs multisite

## Open Questions

1. Should we log the site URL/name in addition to the blog ID for readability?
2. Should `switch_blog` events be logged, or is that too noisy?
3. How do super admin role changes interact with site-level access?
