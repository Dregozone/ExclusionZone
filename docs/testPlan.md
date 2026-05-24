# ExclusionZone Test Plan (for `docs/spec.md`)

## 1. Purpose
Define validation coverage for each major requirement in the initial specification before implementation begins.

## 2. Test Coverage Matrix

| Spec Area | Validation Goal | Test Type | Example Test Cases |
|---|---|---|---|
| Project overview | Scope matches text-based post-nuclear MMORPG vision | Documentation review | Confirm multiplayer, text-based gameplay, survival loop, and non-P2W premium intent are documented |
| Technical stack | Stack aligns with requested technologies | Documentation review | Confirm Laravel 13, Livewire v4 single-file components, Flux Pro, Tailwind, Alpine.js are listed |
| User types | All required user types are present | Documentation review | Confirm admin/moderator/premium/user/guest are explicitly defined |
| Role authority rules | Admin-only role changes are enforced | Authorization tests (future) | Admin can change user role; non-admin roles are forbidden |
| Users->roles->tasks | Role-task mapping resolves permissions correctly | Model/policy tests (future) | For each role, assert allowed/denied task keys |
| Users->location->city->actions | City actions are location-driven | Integration tests (future) | User in city X only receives actions for city X |
| Users->location->city screen data | City details populate from location | Integration tests (future) | Screen includes city and country details from current location |
| Seed/reference models | Country/city weather and trouble values are realistic and bounded | Seed/data tests (future) | Verify ranges 0-100 and expected high-rain/high-trouble examples |
| Skill model | Skills and level tiers support progression rules | Unit/data tests (future) | Verify required skill keys and tier unlock boundaries |
| City action examples | Post-nuclear city actions are documented per city | Documentation review | Each listed city has fitting action examples |
| Premium benefits | Premium remains cosmetic-only | Authorization/gameplay tests (future) | Premium can equip cosmetics; no combat/resource/stat advantage |
| Moderator controls | Moderator powers are constrained appropriately | Authorization tests (future) | Moderator can mute temporarily; cannot change roles |
| Additional considerations | Open questions are available for stakeholder review | Documentation review | Ensure additional considerations section exists at end of spec |

## 3. Detailed Future Test Cases (Implementation Phase)

### 3.1 Authorization
1. Admin role change permission
   - Given admin + target user
   - When admin submits role change
   - Then role updates successfully and is audited
2. Non-admin denied role change
   - Given moderator/premium/user attempts role change
   - Then request is denied and no data changes
3. Moderator temporary mute
   - Given moderator and chat user
   - Then mute applies for configured duration and expires correctly

### 3.2 Location and City Actions
1. City action filtering
   - Given user location = Detroit
   - Then only Detroit actions are returned
2. City detail hydration
   - Given location with city/country references
   - Then screen includes city weather/trouble metadata
3. Travel updates availability
   - Given user changes city
   - Then action list updates to new location

### 3.3 Progression and Skills
1. Skill threshold checks
   - Given action requires hunting level 12
   - Then level 11 denied; level 12 allowed
2. Risk/reward alignment
   - Given high-risk action
   - Then reward table includes corresponding higher-tier outcomes

### 3.4 Premium Cosmetics (Non-P2W)
1. Cosmetic equip success
   - Given premium user
   - Then cosmetic equip succeeds
2. No gameplay advantage
   - Given premium and non-premium with same baseline stats/gear
   - Then outcomes remain equivalent absent other variables

### 3.5 Data Integrity / Seeds
1. Country-city linkage integrity
   - Every city references an existing country
2. Percentage range validation
   - Weather/trouble values remain within 0-100
3. Required entities exist
   - Roles, tasks, skills, countries, and cities are present after seed

## 4. Entry / Exit Criteria

### Entry
- Spec approved by stakeholders
- Data model strategy agreed (including premium modeling approach)
- Auth and location scope finalized for v1

### Exit
- Critical auth tests pass (admin role changes, moderator limits)
- Location-driven action tests pass
- Premium non-P2W tests pass
- Seed integrity tests pass
- No critical unresolved defects in scoped features

## 5. Risks and Follow-ups
- Premium representation (role vs subscription flag) must be decided early.
- PvP policy choice can significantly change combat and moderation tests.
- Dynamic weather/trouble systems may require simulation-style tests later.
