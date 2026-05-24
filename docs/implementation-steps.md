# ExclusionZone Implementation Steps (Agent-Actionable)

This document converts `docs/spec.md` and `docs/testPlan.md` into sequential implementation tasks. Execute steps in order.

## 1. Confirm scope and implementation baseline
1. Read `docs/spec.md` and `docs/testPlan.md` fully.
2. Record open decisions from spec section 11 and test plan risks:
   - Premium modeling approach (role vs subscription overlay)
   - PvP policy (always-on/opt-in/event-based)
   - Static vs dynamic weather/trouble behavior
3. Confirm entry criteria from `docs/testPlan.md` section 4 are satisfied before coding.

**Deliverables:** agreed scope notes, v1 assumptions, no unresolved blockers for auth/location/premium scope.

## 2. Bootstrap Laravel + UI stack foundation
1. Initialize Laravel 13 project structure (or align existing app to Laravel 13 conventions).
2. Install and configure Livewire starter kit using latest compatible release.
3. Enable Livewire v4 single-file components + Flux Pro integration path.
4. Configure Tailwind CSS and Alpine.js.
5. Add minimal environment configuration for local development and database connectivity.

**Deliverables:** app boots successfully, baseline UI tooling available, framework stack matches spec section 2.

## 3. Create access-control schema and seed data
1. Create migrations/models for:
   - `roles`
   - `tasks`
   - `role_task` (pivot)
2. Seed exact role keys: `admin`, `moderator`, `premium`, `user`, `guest`.
3. Seed task keys from spec section 4.1 (`view_public_pages` through `view_admin_dashboard`).
4. Seed `role_task` mapping exactly as defined in spec section 4.1.
5. Add referential integrity and uniqueness constraints (role key/task key uniqueness; valid FK links).

**Deliverables:** role/task/pivot schema and seeds fully aligned with spec tables.

## 4. Implement core authorization policies
1. Create authorization layer (policies/gates/service) resolving permissions through `users -> roles -> tasks`.
2. Enforce hard rule: only `admin` can execute `role_change_user`.
3. Enforce moderator limits: allow mute/moderate chat tasks, deny role changes.
4. Enforce premium rule: premium cosmetics require premium entitlement only; do not grant via staff/admin role.
5. Add audit logging for role changes (actor, target, old role, new role, timestamp).

**Deliverables:** centralized, testable authorization path with explicit admin/moderator/premium constraints.

## 5. Build world reference schema and seed values
1. Create migrations/models for:
   - `countries`
   - `cities`
   - `user_locations`
   - `city_actions`
2. Seed countries and cities exactly as defined in spec section 4.2.
3. Seed city action examples from spec (`scavenge_reactor_zone`, `salvage_factory_line`, `jungle_hunt`, `deep_sea_fishing`).
4. Add FK constraints (`cities.country_id`, `user_locations.country_id`, `user_locations.city_id`, `city_actions.city_id`).
5. Add bounded validation/check constraints for `rain_chance_pct` and `trouble_chance_pct` (0-100).

**Deliverables:** location/action model supports `users -> location -> city -> actions` and valid percentages.

## 6. Build progression and premium-cosmetic schema
1. Create migrations/models for:
   - `skills`
   - `skill_level_rules`
   - `premium_cosmetics`
2. Seed 13 skills and tier rules exactly as listed in spec section 4.3.
3. Seed premium cosmetics with `gameplay_bonus = none`.
4. Link `city_actions.skill_key` + `min_level` to progression checks.

**Deliverables:** progression model and cosmetic model ready for feature logic.

## 7. Implement location-driven gameplay queries
1. Build service/query layer that returns only actions for the user’s current city.
2. Build screen data composition per spec section 10:
   - user + role data
   - user location + city + country data
   - city actions + skill requirements
   - optional overlays (weather/trouble/local events)
3. Implement travel/update flow so city changes refresh available actions.

**Deliverables:** city-specific action availability and hydrated location screen data.

## 8. Implement feature behaviors by user type
1. Guest: public pages, registration, login access.
2. User: move cities, gather resources, chat, trade, combat hooks.
3. Premium: equip cosmetics and visual personalization only.
4. Moderator: temporary mute and chat moderation only.
5. Admin: role changes and admin controls.

**Deliverables:** user stories in spec section 5 are represented in route/action permissions and feature handlers.

## 9. Enforce non-pay-to-win premium guarantee
1. Verify premium features are restricted to cosmetics/UI/profile flair/loadout visuals.
2. Ensure no premium-only modifiers alter combat, stats, yields, trade outcomes, or resource gain.
3. Add explicit guards preventing gameplay bonus fields from being applied.

**Deliverables:** premium pathway remains cosmetic-only by implementation and validation.

## 10. Build automated tests from `docs/testPlan.md`
1. Add authorization tests:
   - admin role change succeeds + audit exists
   - non-admin role change denied + no state mutation
   - moderator mute applies/expires
2. Add location/city integration tests:
   - location-based city action filtering
   - city/country detail hydration
   - travel updates action availability
3. Add progression tests:
   - skill threshold deny/allow boundaries
   - risk/reward tier alignment checks
4. Add premium tests:
   - premium cosmetic equip success
   - premium vs non-premium gameplay parity
5. Add data integrity tests:
   - all cities link to valid countries
   - weather/trouble percentages within 0-100
   - required entities seeded (roles/tasks/skills/countries/cities)

**Deliverables:** automated tests cover all items from test plan sections 2 and 3.

## 11. Validate against entry/exit criteria
1. Confirm critical auth tests pass.
2. Confirm location-driven action tests pass.
3. Confirm non-P2W premium tests pass.
4. Confirm seed integrity tests pass.
5. Confirm no critical unresolved defects in scoped features.

**Deliverables:** exit criteria in `docs/testPlan.md` section 4 are fully satisfied.

## 12. Final implementation review and handoff
1. Verify tech stack references still match actual implementation.
2. Re-check unresolved questions from spec section 11 and document deferred decisions.
3. Produce release notes summarizing implemented scope and known follow-ups.
4. Provide a traceability map (spec requirement -> implementation module -> test case).

**Deliverables:** implementation package is auditable, test-backed, and ready for stakeholder review.
