# Email audit by role

Audit of outbound email paths in lanlist, organized by recipient role/cohort.

**Generated:** 2026-05-22  
**Scope:** `public/` application code (CLI newsletter runner included)

---

## Infrastructure

All email goes through `sendEmail()` in `public/includes/functionality/misc.php`. That function:

- Prefixes the subject with `SITE_TITLE - `
- Appends a minimal standard footer (`- lanlist.info`) unless disabled
- Sends via SMTP when `SEND_EMAIL` is true (`public/includes/config.php`)
- Logs every successful send to `email_log`

Bulk helpers:

| Function | Recipients |
|---|---|
| `sendEmailToAdmins()` | Primary group **admins** (gid 102, `ADMIN_GID`) |
| `sendEmailToGroup($gid)` | All users whose **primary group** matches |
| `sendModeratorNewsletter()` | **Moderators** (gid 103, `MODERATOR_GID`), filtered by per-user frequency |

**Important:** recipient targeting uses `users.group` (primary group) only. Organizer affiliation (`users.organization`) is a separate axis used for event notifications.

### Footers

| Type | Footer |
|---|---|
| Automated (`sendEmail()` default) | Plain suffix: `- {SITE_TITLE}` |
| Manual staff email (`FormSendEmailToUser`) | Long HTML footer with opt-out / contact info; standard footer disabled |

---

## Groups in the database

| gid | Title | Typical permissions | Users with email (approx.) |
|---|---|---|---|
| 2 | Users | (default registered users) | 103 |
| 102 | admins | `SUPERUSER` | 1 |
| 103 | Moderators | `MODERATOR`, moderation tools, `TOGGLE_EVENT_PUBLISHED`, etc. | 6 |

Organizer-linked users (`organization IS NOT NULL`): 47 total — mostly gid 2 Users (41), plus 5 Moderators and 1 admin.

---

## Emails by recipient role

### Admins (gid 102)

| Trigger | File / entrypoint | When | Subject (before `SITE_TITLE -` prefix) | Content |
|---|---|---|---|---|
| New user registration | `FormRegister.php` | Someone completes registration | `New user registration: {username}` | Plain text: username only |

Admins do **not** receive the moderator newsletter (that goes to gid 103).

---

### Moderators (gid 103)

| Trigger | File / entrypoint | When | Subject (before prefix) | Content |
|---|---|---|---|---|
| Moderator newsletter | `ScheduledTaskNewsletter.php` via `scripts/run-newsletter.php` | Scheduled job runs **and** site-checks panel has issues | `Moderator newsletter for {D j M}, N item(s)` | HTML from `newsletter.tpl` |

**Newsletter contents:** events with issues, unpublished organizers, organizers with no upcoming events (same snapshot as `siteChecks.php`).

**Per-user frequency** (`users.moderatorNewsletterFrequency`, set on edit profile):

| Value | Behaviour |
|---|---|
| `daily` (default) | Receive on every run that has issues |
| `fridays_only` | Receive only when the job runs on a Friday |

**Not sent when:** the panel has zero issues, even if the job runs.

Moderators linked to an organizer (`users.organization` set) can **also** receive event publish/unpublish emails (see Regular users / organizer-linked below).

---

### Regular users (gid 2, “Users”)

| Trigger | File / entrypoint | When | Subject (before prefix) | Content |
|---|---|---|---|---|
| Password reset | `FormRequestPasswordReset.php` | Guest submits email on reset form (must not be logged in) | `Password Reset Requested` | Plain text reset code |
| Event published / unpublished | `misc.php?action=toggleEvent` | Moderator toggles publish state | `Event: {title} has been published!` / `…unpublished.` | HTML from `email.eventToggled.tpl` |

**Event toggle recipients:** every user where `users.organization = event.organizer`, **regardless of primary group**. In practice mostly gid 2 Users; also includes organizer-linked moderators/admins.

**Not sent to regular users:**

- No welcome or confirmation email on registration
- No newsletter
- No admin-style notifications

---

### Any address (manual staff email)

| Trigger | File / entrypoint | When | Who can send | Content |
|---|---|---|---|---|
| Send email to user | `FormSendEmailToUser.php` | Staff submits form from user management | Requires `SEND_EMAIL` priv | Custom HTML; templates: `default`, `eventToggled`, `addYourRecentEvents` |

**Recipient:** whatever address is entered — usually the target user’s profile email, not strictly enforced.

**Templates:**

| Template | Typical use |
|---|---|
| `email.default.tpl` | Generic message |
| `email.eventToggled.tpl` | Publish/unpublish notification |
| `email.addYourRecentEvents.tpl` | “Nag to add recent events” (linked from `viewUser.php`) |

Uses custom HTML footer; calls `sendEmail(..., false)` to skip the standard footer.

---

## Summary matrix

| Email type | Recipients | Triggered by | Automated? |
|---|---|---|---|
| New registration notice | Admins | User self-registration | Yes |
| Password reset code | Any registered user (matched by email) | User self-service (logged out) | Yes |
| Event publish / unpublish | Organizer-linked users | Moderator (`TOGGLE_EVENT_PUBLISHED`) | Yes |
| Moderator newsletter | Moderators (gid 103) | OliveTin / `run-newsletter.php` | Yes |
| Manual staff email | Any address | Staff with `SEND_EMAIL` (see gaps) | No |

---

## What never gets email

- Users without a valid email on their profile (skipped with `SEND_EMAIL_INVALID` log)
- Moderators on `fridays_only` when the job runs Monday–Thursday
- Anyone when `SEND_EMAIL` is false in config
- Guests / non-registered visitors (except they can trigger a reset email to a registered address)
- New registrants (no welcome / verification email)

---

## Gaps and quirks

1. **`SEND_EMAIL` permission is undefined** — `FormSendEmailToUser` requires it, but it is not in the `permissions` table. Only `SUPERUSER` (admins) can pass the check via libAllure’s bypass. The “Send email” link on `viewUser.php` is shown to `EDIT_USER` (moderators), who will fail at form load.

2. **Registration emails admins, not the new user** — no verification or welcome message to the registrant.

3. **Role vs organizer** — event notifications follow `users.organization`, not moderator/admin role.

4. **Two footer styles** — automated vs manual staff emails (see Infrastructure).

5. **`USER_EMAIL_LOG` permission is undefined** — referenced in `viewUser.php` to show recent `email_log` rows, but permission does not exist in DB; history is never shown.

6. **HTML content-type for all mail** — `sendEmail()` sets `Content-Type: text/html` even for plain-text bodies (e.g. password reset).

---

## Code references

| Path | Role |
|---|---|
| `public/includes/functionality/misc.php` | `sendEmail`, `sendEmailToAdmins`, `sendEmailToGroup`, `sendModeratorNewsletter` |
| `public/includes/classes/ScheduledTaskNewsletter.php` | Moderator newsletter task |
| `scripts/run-newsletter.php` | CLI runner (OliveTin) |
| `public/includes/classes/FormRegister.php` | Admin notification on signup |
| `public/includes/classes/FormRequestPasswordReset.php` | Password reset code |
| `public/misc.php` (`toggleEvent`) | Event publish/unpublish to organizer users |
| `public/includes/classes/FormSendEmailToUser.php` | Manual staff email |
| `public/includes/templates/newsletter.tpl` | Newsletter body |
| `public/includes/templates/email.*.tpl` | Manual / event email templates |
