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
| Users -> location -> city -> actions | Action list is filtered by current city and updates after travel | `tests/Feature/GameMvpTest.php` (`city actions are filtered by the current location...`) | Done |
| City action execution | Action grants rewards and progression | `tests/Feature/GameMvpTest.php` (`performing a city action grants experience...`) | Done |
| Skill thresholds | Below-min-level denied; boundary level allowed | `tests/Feature/GameMvpTest.php` (`skill thresholds deny and allow access...`) | Done |
| Premium non-P2W | Premium can equip cosmetics only; outcomes stay parity | `tests/Feature/GameMvpTest.php` (`premium cosmetic equip succeeds...`) | Done |
| Moderator controls | Moderator mute allowed and expiration works | `tests/Feature/GameMvpTest.php` (`moderators can temporarily mute users and the mute expires`) | Done |
| Seed/reference integrity | Roles/skills/countries/cities seeded; percentages bounded | `tests/Feature/GameMvpTest.php` (`reference data from the specification is seeded and bounded`) | Done |
| Auth starter-kit flows | Registration/login/reset/verification/2FA/profile/security | `tests/Feature/Auth/*`, `tests/Feature/Settings/*` | Done |

## 5. Planned Test Backlog (Next TDD Slice)

1. **Combat loop integration**
   - User with combat permission can initiate combat.
   - User without combat task is denied with UX-safe feedback.
2. **Chat moderation workflow**
   - Moderator can remove/flag chat messages.
   - Non-moderator cannot perform moderation actions.
3. **Trade allow-path**
   - Standard user can access/create trade flow (not just denial-path coverage).
4. **Admin dashboard boundaries**
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
- Dynamic weather/trouble systems may require simulation-style tests in later phases.
