# Moderator engagement report

**Generated:** 2026-05-24  
**Scope:** Lanlist volunteer moderation — how to keep unpaid community moderators checking and engaging with database quality work.

---

## Executive summary

Lanlist depends on a small pool of unpaid moderators (gid 103, currently ~6 users with email) to maintain events, organizers, and venues. The platform is **strong at surfacing problems** (site checks, random organizer queue, issue-driven newsletter) but **weak at rewarding problem-solving** (no impact visibility, no positive feedback loop, no regional tooling in software, broken outreach permissions).

The highest-leverage changes are not gamification gimmicks. They are:

1. **Visible impact** — show moderators that their work matters and is counted.
2. **Lower friction** — fix broken tools, prioritize tasks, reduce cognitive load.
3. **Light ownership** — bounded queues and optional claims so work feels personal, not infinite.
4. **Community recognition** — opt-in credit, Discord rituals, occasional positive email.

Most recommendations can be implemented incrementally using existing audit logs, `lastChecked`, the random queue, and the newsletter pipeline.

---

## Current moderation model

### Workflows today

| Workflow | Entry point | Purpose |
|---|---|---|
| Site health review | `public/siteChecks.php` | Events with issues, silenced ticket warnings, unpublished organizers, stale organizers with no upcoming events |
| Random organizer sweep | `public/moderation-rando.php` | One unchecked organizer (45+ days, no future events) selected at random |
| Join request handling | `public/joinRequests.php` | Approve/deny organizer membership requests |
| Inline fixes | Organizer/event pages | Quick edits (Discord URL, publish state, etc.) |
| Background awareness | Moderator newsletter | Email snapshot when the control panel has issues (`scripts/run-newsletter.php`) |

Data for the control panel comes from `lanlistFetchModeratorPanelData()` in `public/includes/functionality/site_checks.php`. Issue detection uses `EventsChecker` in `public/includes/classes/EventsChecker.php`.

### What already works well

1. **Automated issue surfacing** — moderators do not need to hunt; problems appear in lists with a badge count in the admin menu (`public/includes/widgets/adminBox.php`).
2. **Random queue reduces decision fatigue** — `moderation-rando.php` answers “where do I start?” for organizer maintenance.
3. **Audit trail exists** — actions like `MODERATION_MARK_STALE`, `EDIT_EVENT`, `EDIT_ORGANIZER`, and `JOIN_REQUEST_APPROVED` are logged via `Logger::messageAudit()` / `messageNormal()` and viewable in `public/listLogs.php`.
4. **Respect for moderator time** — per-user newsletter frequency (`daily`, `fridays_only`, `never`) in `users.moderatorNewsletterFrequency`.
5. **Honest volunteer framing** — `public/includes/templates/jobAdverts.tpl` and the site banner set expectations: unpaid, regional, ~couple of hours per month.

### Structural gaps that reduce engagement

| Gap | Evidence | Effect |
|---|---|---|
| **Labor is invisible** | Logs exist but no moderator dashboard or public credit | Work feels unrewarded and uncounted |
| **Only negative triggers** | Newsletter sends only when `lanlistModeratorPanelIssueCount() > 0` | Email = bad news; silence = no feedback |
| **No ownership model** | All moderators see the same global lists | “Someone else will fix it” |
| **Regional roles not in software** | `jobAdverts.tpl` mentions EMEA/APAC/NOAM/SAAM; DB has no region fields | Recruiting promise does not match tooling |
| **Broken outreach tool** | `FormSendEmailToUser` requires undefined `SEND_EMAIL` permission — see `docs/email-audit-by-role.md` | UI shows “Send email”; moderators often cannot use it |
| **High cognitive load** | Control panel is four separate tables with no prioritization | Easy to feel overwhelmed |
| **No onboarding path** | `moderatorControlPanel.tpl` is minimal | New volunteers lack a “first 30 minutes” guide |

---

## Recommendations

Organized by impact vs effort. All respect the unpaid volunteer nature of the role.

### Tier 1 — High impact, relatively low effort

#### 1. Moderator impact panel (private)

Add a small dashboard on `siteChecks.php` or a new page showing:

- Open issues vs 7/30 days ago (trend or delta)
- This moderator’s recent actions (from `logs` where content references their username)
- Team totals: organizers checked this month, issues resolved, join requests handled

**Why:** Volunteers persist when they see progress. Today, fixing an issue makes a row disappear with no record that *they* did it.

**Data source:** Existing `logs` table — `EDIT_EVENT`, `MODERATION_NO_EVENTS`, `JOIN_REQUEST_APPROVED`, etc.

#### 2. “Good news” emails, not only issue dumps

Extend `ScheduledTaskNewsletter` / `scripts/run-newsletter.php` to optionally send:

- **Weekly digest** even when issue count is zero: “Site health: all clear” plus team stats
- **Improvement notices**: “Issue count dropped from 42 → 28 since last run”

**Why:** Positive intermittent reinforcement beats alarm-only email. The best outcome today is *no email*, which provides zero encouragement.

#### 3. Fix moderator email permissions

Define `SEND_EMAIL` in the `permissions` table and grant it to gid 103, or relax `FormSendEmailToUser` to accept `MODERATOR`.

**Why:** Moderators who contact organizers close loops faster. The current gap is actively demoralizing — UI promises capability the backend denies.

#### 4. Attribute “last checked by” on organizers

When `lastChecked` is updated (`public/misc.php`, `moderation-rando.php`), also store `lastCheckedBy` (user id). Show it on organizer moderator fields.

**Why:** Lightweight credit within the mod team; helps admins spot inactive regions or moderators who need support.

---

### Tier 2 — Medium effort, strong engagement gains

#### 5. Soft task ownership (“claim” / “assigned to me”)

On site-check rows and random queue items, allow a moderator to claim an organizer or event issue for ~7 days. Show claimant in the list; allow release.

**Why:** Reduces duplicate work and the bystander effect. A personal queue of 3–5 items beats an infinite shared backlog.

**Start with:** Organizers in `organizersWithNoEvents.tpl` — low risk, high clarity.

#### 6. Prioritize and score the control panel

Replace flat tables with a sorted work queue, e.g.:

| Priority | Example |
|---|---|
| High | Unpublished organizer with upcoming event in &lt;14 days |
| Medium | Event with missing tickets, starts in &lt;30 days |
| Low | Organizer unchecked 90+ days, no events |

Add filters: “my region”, “quick wins”, “needs website visit”.

**Why:** Moderators on limited time need curated work. The random queue already does this for one workflow; extend the idea to site checks.

#### 7. Wire up regional moderation in the database

Add `users.moderatorRegion` (EMEA, APAC, etc.) and optionally `organizers.region` or infer from venue country. Filter site checks and random queue by region by default.

**Why:** Aligns software with `jobAdverts.tpl`. “I maintain UK/Ireland” is easier to sustain than “I maintain everything”.

#### 8. Guided onboarding for new moderators

A “First shift” checklist linked from the control panel:

1. Review 1 item in random queue
2. Clear 1 event issue
3. Handle 1 join request (if any)
4. Join Discord mod channel
5. Set newsletter preference in profile

**Why:** Reduces activation energy. Many volunteers churn before their first successful contribution.

#### 9. One-click actions from the newsletter

The newsletter already links to events and “mark tickets not yet released” (`newsletter.tpl`). Extend with:

- “Mark organizer last checked” links
- “Assign to me” deep links
- Pre-filled contact-organizer templates (once email permission is fixed)

**Why:** Converts passive inbox scanning into completed work.

---

### Tier 3 — Community and recognition (process + light product)

#### 10. Public “Contributors” page (opt-in)

List moderators who opt in, with region, “member since”, optional blurb. Link from footer or About.

**Why:** Non-monetary recognition is the primary currency for community volunteers.

**Guardrail:** Opt-in only; some prefer anonymity.

#### 11. Monthly moderator shout-out (Discord + news)

Use `listNews.php` / Discord to highlight organizers checked, issues resolved, new events added. Pull stats from logs; keep tone appreciative, not competitive.

**Why:** Social proof that others are active — important when the team is small.

#### 12. Moderator office hours on Discord

Recurring 30-minute slot where moderators co-work through the random queue or site checks together.

**Why:** Turns solitary maintenance into social habit. Belonging often matters more than badges for unpaid roles.

#### 13. Small tangible perks (non-pay)

Examples: Discord role, early feature access, stickers/shirt for sustained contribution, conference ticket if budget allows.

**Why:** Signals the org values the role beyond “free labor for site quality.”

---

### Tier 4 — Deeper investments (longer term)

#### 14. Leaderboard with anti-toxicity design

If adding rankings:

- Rank **teams/regions**, not individuals, by default
- Weight quality (issues staying fixed) over quantity
- Never show “bottom” performers publicly

Pure individual leaderboards often burn out volunteers.

#### 15. Organizer self-service nudges

Automated emails to organizer-linked users (`users.organization`) when their events have issues — with a link to fix. Moderators **review** rather than **hunt**.

Aligns with existing event toggle emails; shrinks the backlog moderators feel responsible for.

#### 16. Mobile-friendly moderation views

Table-heavy templates (`eventsWithIssues.tpl`, `moderation.tpl`) are desktop-oriented. A simplified mobile “next task” view would increase touch frequency.

---

## What not to do

1. **Don’t add pay-for-performance** without budget — conflicts with “volunteer positions, not paid.”
2. **Don’t increase email volume without positive content** — more alarms will push moderators toward newsletter `never`.
3. **Don’t expose moderator activity punitively** — “inactive moderator” callouts cause shame-driven churn.
4. **Don’t over-gamify** — points/badges alone do not sustain data-quality work.
5. **Don’t expand scope before fixing broken tools** — the `SEND_EMAIL` permission gap undermines trust.

---

## Suggested rollout

| Phase | Timeline | Deliverables |
|---|---|---|
| **1** | 1–2 sprints | Fix `SEND_EMAIL` for moderators; add `lastCheckedBy`; basic impact stats on control panel |
| **2** | 2–4 sprints | Positive newsletter variant; regional fields; onboarding checklist |
| **3** | Ongoing | Soft claims; prioritized queue; public contributors page; Discord shout-outs |

```
Fix SEND_EMAIL → Impact dashboard + lastCheckedBy → Good-news newsletter
    → Regional filters + onboarding → Claims + prioritized queue → Public recognition
```

---

## Success metrics

Track monthly from `logs` and new fields:

| Metric | Target | Why |
|---|---|---|
| Active moderators (≥1 logged action / 30 days) | Stable or ↑ | Retention |
| Median organizer `lastChecked` age | ↓ | Coverage |
| Open issue count (`lanlistModeratorPanelIssueCount`) | ↓ or stable | Quality |
| Time from issue appearance → resolution | ↓ | Responsiveness |
| Random queue completions / moderator | ↑ | Engagement |
| Newsletter `never` opt-in rate | ↓ | Communication health |
| New moderator time-to-first-action | ↓ | Onboarding quality |

---

## Code references

| Path | Role |
|---|---|
| `public/siteChecks.php` | Moderator control panel |
| `public/moderation-rando.php` | Random organizer queue |
| `public/includes/functionality/site_checks.php` | Panel data and issue counts |
| `public/includes/classes/EventsChecker.php` | Event issue detection |
| `public/includes/classes/ScheduledTaskNewsletter.php` | Issue-driven newsletter |
| `scripts/run-newsletter.php` | CLI newsletter runner (OliveTin) |
| `public/includes/functionality/misc.php` | `sendModeratorNewsletter`, frequency prefs |
| `public/listLogs.php` | Audit log viewer |
| `public/includes/templates/jobAdverts.tpl` | Volunteer recruitment copy |
| `docs/email-audit-by-role.md` | Email paths and known permission gaps |

---

## Conclusion

Lanlist’s moderation tooling is **good at finding problems** but **weak at rewarding problem-solving**. Volunteer moderators stay engaged when work feels **bounded**, **recognized**, and **impactful** — not when they receive another list of red rows.

The fastest morale wins: fix broken moderator email access, show moderators their own stats, and occasionally email “things are better because of you” — not only “things are broken.”
