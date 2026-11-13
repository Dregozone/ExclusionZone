# ExclusionZone Test Plan (TDD Coverage and Traceability)

## 1. Purpose
Define a comprehensive TDD-first validation plan that maps requirements in `docs/spec.md` to automated tests, planned tests, and live progress tracking.

## 2. Live Progress (Passing vs Total)

Primary command:

```bash
php artisan test --compact
```

Current baseline on this branch: **43 passed, 4 skipped, 47 total**.

Progress rule:
- **Done** = required test exists and passes.
- **Planned** = requirement is documented here but test is not yet implemented.
- **Blocked** = requirement depends on unresolved scope decisions in `docs/spec.md` section 11.

## 3. Role and User-Story Coverage Matrix

| Role | User Story (from `docs/spec.md`) | Coverage | Status |
|---|---|---|---|
| guest | Read game info/lore | `tests/Feature/GameMvpTest.php` (`landing page is available from the home route`) | Done |
| guest | Register account | `tests/Feature/Auth/RegistrationTest.php`, `tests/Feature/GameMvpTest.php` (`newly registered users receive...`) | Done |
| guest | Log in | `tests/Feature/Auth/AuthenticationTest.php` | Done |
| user | Move between cities and see city-specific actions | `tests/Feature/GameMvpTest.php` (`city actions are filtered by the current location...`) | Done |
| user | Gather resources | `tests/Feature/GameMvpTest.php` (`performing a city action grants experience and adds items to inventory`) | Done |
| user | Chat and trade access hooks | `tests/Feature/GameMvpTest.php` (`feature hook denials redirect back...`) | Done (deny-path) |
| user | Trade items with another player at a trade post | `tests/Feature/TradingTest.php` | Done |
| user | Engage in combat | `tests/Feature/GameMvpTest.php` (`reference data from the specification is seeded and bounded`) + combat task seed coverage | Planned (full combat loop) |
| premium | Equip cosmetic outfits/themes | `tests/Feature/GameMvpTest.php` (`premium cosmetic equip succeeds and does not change gameplay rewards`) | Done |
| premium | No gameplay advantage from premium | `tests/Feature/GameMvpTest.php` (`premium cosmetic equip succeeds and does not change gameplay rewards`) | Done |
| moderator | Temporarily mute abusive users | `tests/Feature/GameMvpTest.php` (`moderators can temporarily mute users and the mute expires`) | Done |
| moderator | Moderate/remove inappropriate chat content | Permission and role-task seed coverage in `tests/Feature/GameMvpTest.php` | Planned (message-level moderation workflow) |
| admin | Change another user role | `tests/Feature/GameMvpTest.php` (`admin can change another user role and non admins cannot`) | Done |
| admin | Access admin controls | `tests/Feature/GameMvpTest.php` (`authenticated users can reach the change user role page...`) | Done |

## 4. Requirement Traceability Matrix

| Requirement Area | Required Behavior | Test Coverage | Status |
|---|---|---|---|
| Users -> roles -> tasks | Authorization enforced by role task mapping | `tests/Feature/GameMvpTest.php` (role-change allow/deny; guest denial to city-action and feature hook) | Done |
| Admin-only role changes | Only admin can change roles; audit is recorded | `tests/Feature/GameMvpTest.php` (`admin can change another user role and non admins cannot`) | Done |
| Granting admin from the server | `php artisan grant:admin {email}` makes an account admin (so the first admin can be created), logs the audit, and fails for an unknown email | `tests/Feature/GrantAdminCommandTest.php` | Done |
| Users -> location -> city -> actions | Action list is filtered by current city and updates after travel | `tests/Feature/GameMvpTest.php` (`city actions are filtered by the current location...`) | Done |
| City action execution | Action grants rewards and progression | `tests/Feature/GameMvpTest.php` (`performing a city action grants experience...`) | Done |
| Skill thresholds | Below-min-level denied; boundary level allowed | `tests/Feature/GameMvpTest.php` (`skill thresholds deny and allow access...`) | Done |
| Premium non-P2W | Premium can equip cosmetics only; outcomes stay parity | `tests/Feature/GameMvpTest.php` (`premium cosmetic equip succeeds...`) | Done |
| Moderator controls | Moderator mute allowed and expiration works | `tests/Feature/GameMvpTest.php` (`moderators can temporarily mute users and the mute expires`) | Done |
| Player trading | Both players must stand at the same trade post (one per continent); request, accept/reject, add/remove item quantities, commit or cancel; completes only when both commit; quest items cannot be traded; changing the table withdraws commitments; nearest trade post on the continent is suggested; updates broadcast on each trader's private channel | `tests/Feature/TradingTest.php` | Done |
| Dynamic weather | Hourly per-city weather roll; bad weather lengthens work/travel timers, grants bonus XP, and can lose loot; weather is locked when work starts | `tests/Feature/WeatherTest.php` | Done |
| Enemies and combat | City actions using a combat skill fight an enemy in that city testing the same skill; each loop cycle is one round; skill, health and the best carried weapon change damage dealt and blocked (premium has no effect); winning pays enemy XP, rolled loot and the action reward, then lines up the next enemy; dropping to the flee threshold ends the loop without reward; a killing blow fires `SurvivorDied` with `HarmCause::Combat`; enemies and weapon attack bonuses are set on the admin pages | `tests/Feature/CombatTest.php` | Done |
| Continuous work | City actions repeat until stopped; finished cycles pay out automatically, including while the player is away; the loop ends on stop, missing items, lost access, or an 8 hour limit, and reports why; travel and the Brain Scorcher stay one-shot | `tests/Feature/WorkLoopTest.php` | Done |
| Multi-stop travel | Players can travel to any connected city; the route is validated hop by hop, each leg starts on its own when the last arrives (also while away), arrival needs no confirmation, cancel keeps the player at the stop they reached, and a broken next leg ends the route with a report | `tests/Feature/TravelRouteTest.php` | Done |
| Hunger and fatigue | Finished work cycles add each action's hunger/fatigue cost and travel legs add a configured cost (capped at 100); thresholds slow every timer without stopping work; fatigue falls while resting without work; eating food (Borscht, Vodka Fish, rations…) eases needs, also mid-work; Borscht is cooked in Kyiv; costs and food values are set on the admin pages, the rest in `config/survival.php` | `tests/Feature/HungerAndFatigueTest.php` | Done |
| Healing | Medical items (bandages, antiseptic, field medkits) are used, not eaten, from Survivor condition to restore health, also mid-work, and are refused when not hurt; bandages are looted in Kyiv and rolled in Warsaw, antiseptic distilled in Manaus; field clinics in Kyiv and Warsaw restore health each cycle for a fee and stop at full health; health slowly mends while resting unless starving (`config/survival.php`); admins set an action's health reward and mark items medical | `tests/Feature/HealingTest.php` | Done |
| Health and death | Survivors start at 100 health; each work cycle, travel leg or Scorcher burn finished while starving or spent costs configured health; low-health thresholds slow timers; food restores its health value; at 0 health the `SurvivorDied` event fires and the survivor is rescued (work stops, health revives, they wake at the nearest trade post on their continent, scavengers take a configured share of each non-quest stack, work and travel are blocked until a recovery period ends, a death report shows on the dashboard until dismissed) | `tests/Feature/HealthTest.php` | Done |
| Environmental harm | Work cycles and travel legs finished in a storm can cost health; irradiated cities (admin-editable radiation damage) cost health for every cycle worked and leg arrived there; city actions have an admin-editable accident chance and damage; injuries are reported with the cycle or arrival and can kill | `tests/Feature/EnvironmentalHarmTest.php` | Done |
| Trouble on arrival | Arriving in a city rolls its trouble chance (full on a first visit, a configured share on return visits; starting city counts as visited); trouble holds the survivor and blocks actions, travel and quest steps, and stops a multi-stop route until dealt with; attacking resolves a fight from combat skill and health (win: XP, loot, part damage; loss: full damage via `HarmCause::Trouble`); buying in takes chosen non-quest goods worth the demand (item value = rarity); running away travels back on a timer that cannot be canceled; winning or paying resumes the route; trouble types are edited on the admin page | `tests/Feature/TroubleTest.php` | Done |
| Seed/reference integrity | Roles/skills/countries/cities seeded; percentages bounded | `tests/Feature/GameMvpTest.php` (`reference data from the specification is seeded and bounded`) | Done |
| Auth starter-kit flows | Registration/login/reset/verification/2FA/profile/security | `tests/Feature/Auth/*`, `tests/Feature/Settings/*` | Done |

## 5. Planned Test Backlog (Next TDD Slice)

1. **Combat loop integration**
   - User with combat permission can initiate combat.
   - User without combat task is denied with UX-safe feedback.
2. **Chat moderation workflow**
   - Moderator can remove/flag chat messages.
   - Non-moderator cannot perform moderation actions.
3. **Admin dashboard boundaries**
   - Admin-only dashboard access constraints for non-admin roles.

## 6. Entry / Exit Criteria

### Entry
- Requirement is mapped to one of: done coverage, planned backlog, or blocked decision.
- Acceptance criteria are written as Given/When/Then before implementation.

### Exit
- Requirement has at least one passing automated test.
- Negative/authorization path is covered for privileged actions.
- `php artisan test --compact` is green for changed scope.
- `composer lint:check` and `npm run build` pass for merged scope.

## 7. Risks and Open Decisions
- Premium representation (role vs subscription overlay) still influences longer-term entitlement tests.
- PvP policy choice can expand/reshape combat and moderation scenarios.
- Dynamic trouble events may require simulation-style tests in later phases (weather rolls are covered with `Lottery` fakes).
