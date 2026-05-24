# ExclusionZone Specification (Initial Draft)

## 1. Project Overview
ExclusionZone is a text-based online multiplayer RPG (MMORPG) set in a post-nuclear-war world (modern day to near-future, ~10 years ahead). Gameplay is inspired by syrnia.com-style interaction loops and adapted to survival, scavenging, social play, and city-based progression.

Core pillars:
- Persistent character progression
- Location-based actions and events
- Multiplayer interaction (chat, trade, proximity, combat)
- Free-to-play with cosmetic-only premium options (no gameplay power)

## 2. Target Technical Stack
- Framework: Laravel 13
- Starter Kit: Laravel Breeze (Livewire stack), using the latest available release at implementation time
- Components: single-file Livewire v4 + Flux Pro
- Styling: Tailwind CSS
- Frontend interactivity: Alpine.js

## 3. User Types and Access Model
User types:
- admin
- moderator
- premium
- user (authenticated)
- guest (unauthenticated)

Rules:
- Only admins may change another user’s role.
- Cosmetic access is premium-entitlement only (staff/admin privileges do not automatically grant premium cosmetics).
- Required relationship checks:
  - `Users -> roles -> tasks`
  - `Users -> location -> city -> actions`
  - `Users -> location -> city` (screen population)

## 4. Recommended Data Models and Seed Values

### 4.1 Access Control

#### `roles`
| id | key | name |
|---|---|---|
| 1 | admin | Administrator |
| 2 | moderator | Moderator |
| 3 | premium | Premium |
| 4 | user | User |
| 5 | guest | Guest |

#### `tasks`
| id | key | description |
|---|---|---|
| 1 | view_public_pages | View public pages |
| 2 | register_account | Register account |
| 3 | login | Authenticate |
| 4 | chat_send | Send chat message |
| 5 | trade_create | Create trade |
| 6 | city_action_perform | Perform city action |
| 7 | combat_initiate | Start combat |
| 8 | equip_cosmetic | Equip cosmetic outfit/theme |
| 9 | mute_user_temporary | Temporarily mute user |
| 10 | role_change_user | Change another user role |
| 11 | moderate_chat_messages | Moderate chat messages |
| 12 | view_admin_dashboard | Access admin controls |

#### `role_task`
| role_key | allowed_tasks |
|---|---|
| guest | view_public_pages, register_account, login |
| user | view_public_pages, login, chat_send, trade_create, city_action_perform, combat_initiate |
| premium | all user tasks + equip_cosmetic |
| moderator | all user tasks + mute_user_temporary, moderate_chat_messages |
| admin | all user tasks + mute_user_temporary, moderate_chat_messages, role_change_user, view_admin_dashboard |

### 4.2 World Data

#### `countries`
| id | continent | country | avg_temp_c | rain_chance_pct | trouble_chance_pct | notes |
|---|---|---|---:|---:|---:|---|
| 1 | Europe | Ukraine | 8 | 35 | 70 | Conflict-scarred zones, high danger events |
| 2 | Europe | Poland | 9 | 45 | 35 | More stable hubs with periodic incidents |
| 3 | North America | United States | 12 | 40 | 55 | Regional variance, urban danger pockets |
| 4 | Asia | Japan | 13 | 50 | 25 | Tech ruins, lower violent-crime baseline |
| 5 | South America | Brazil | 24 | 65 | 60 | High rain and high-risk districts |
| 6 | Africa | South Africa | 17 | 35 | 58 | Resource-rich but volatile zones |

#### `cities`
| id | country_id | city | biome | rain_chance_pct | trouble_chance_pct | baseline_loot_tier |
|---|---:|---|---|---:|---:|---|
| 1 | 1 | Pripyat | irradiated urban ruins | 38 | 80 | high |
| 2 | 1 | Kyiv | metro ruins/fortified sectors | 34 | 72 | medium-high |
| 3 | 2 | Warsaw | fortified urban hub | 46 | 40 | medium |
| 4 | 2 | Gdansk | coastal industrial | 52 | 33 | medium |
| 5 | 3 | Detroit | industrial ruins | 42 | 68 | high |
| 6 | 3 | Seattle | wet urban/coastal | 62 | 45 | medium |
| 7 | 4 | Tokyo | dense megacity sectors | 51 | 28 | medium-high |
| 8 | 4 | Sapporo | cold frontier city | 48 | 22 | medium |
| 9 | 5 | Rio de Janeiro | coastal favela/port mix | 67 | 70 | high |
| 10 | 5 | Manaus | jungle-edge city | 78 | 58 | medium-high |
| 11 | 6 | Johannesburg | inland metro | 32 | 63 | medium-high |
| 12 | 6 | Cape Town | coastal mixed terrain | 41 | 44 | medium |

#### `user_locations`
| field | type | purpose |
|---|---|---|
| user_id | FK -> users.id | character owner |
| country_id | FK -> countries.id | current country |
| city_id | FK -> cities.id | current city |
| district | string nullable | optional sub-zone |
| x_coord / y_coord | int nullable | optional grid position |
| updated_at | timestamp | last movement/action update |

#### `city_actions`
| id | city_id | action_key | skill_key | min_level | risk_level | reward_profile |
|---|---:|---|---|---:|---|---|
| 1 | 1 | scavenge_reactor_zone | scavenging | 15 | extreme | rare components/artifacts |
| 2 | 5 | salvage_factory_line | engineering | 10 | high | metal parts/weapon scraps |
| 3 | 10 | jungle_hunt | hunting | 12 | high | hides/meat/rare herbs |
| 4 | 6 | deep_sea_fishing | fishing | 8 | medium | fish/oils/trade goods |

### 4.3 Progression

#### `skills`
| id | key | display_name | description |
|---|---|---|---|
| 1 | scavenging | Scavenging | Search ruins, vehicles, bunkers |
| 2 | cooking | Cooking | Prepare food and buffs |
| 3 | fishing | Fishing | Catch fish and salvage from water |
| 4 | hunting | Hunting | Track and kill wildlife/hostiles |
| 5 | crafting | Crafting | Build tools, weapons, components |
| 6 | construction | Construction | Build/repair shelters and defenses |
| 7 | combat_melee | Melee Combat | Close-range combat |
| 8 | combat_ranged | Ranged Combat | Firearms/ranged weapons |
| 9 | medicine | Medicine | Heal and craft medkits |
| 10 | engineering | Engineering | Power systems, traps, advanced gear |
| 11 | barter | Barter | Better trade pricing |
| 12 | stealth | Stealth | Avoid detection and ambush |
| 13 | survival | Survival | Resource efficiency and hazard resistance |

#### `skill_level_rules`
| tier | level_range | unlock examples |
|---|---|---|
| novice | 1-9 | basic gathering and weak hunts |
| trained | 10-24 | improved yields and mid-tier recipes |
| veteran | 25-49 | advanced gear and dangerous zones |
| expert | 50+ | elite hunts and rare crafting |

#### `premium_cosmetics`
| id | cosmetic_type | name | gameplay_bonus |
|---|---|---|---|
| 1 | outfit_skin | Wasteland Ranger Set | none |
| 2 | outfit_skin | Neon Hazmat Variant | none |
| 3 | ui_theme | Retro CRT Theme | none |
| 4 | ui_theme | Dark Ash Theme | none |
| 5 | profile_flair | Animated Nameplate | none |

## 5. User Stories by User Type

### Guest
- As a guest, I can read game info/lore.
- As a guest, I can register an account.
- As a guest, I can log in.

### User
- As a user, I can move between cities and see city-specific actions.
- As a user, I can gather resources (wood, food, salvage).
- As a user, I can chat and trade.
- As a user, I can engage in combat.

### Premium
- As a premium user, I can equip cosmetic outfits/themes.
- As a premium user, I can personalize visuals with no gameplay advantage.

### Moderator
- As a moderator, I can temporarily mute abusive users in chat.
- As a moderator, I can moderate/remove inappropriate chat content.

### Admin
- As an admin, I can change another user’s role.
- As an admin, I can access admin controls.

## 6. Role Tasks Matrix
| Task | guest | user | premium | moderator | admin |
|---|---:|---:|---:|---:|---:|
| View public pages | ✅ | ✅ | ✅ | ✅ | ✅ |
| Register/login | ✅ | ✅ | ✅ | ✅ | ✅ |
| Chat send | ❌ | ✅ | ✅ | ✅ | ✅ |
| Trade | ❌ | ✅ | ✅ | ✅ | ✅ |
| Perform city actions | ❌ | ✅ | ✅ | ✅ | ✅ |
| Equip cosmetic items/themes | ❌ | ❌ | ✅ | ❌ | ❌ |
| Temporarily mute user | ❌ | ❌ | ❌ | ✅ | ✅ |
| Change another user role | ❌ | ❌ | ❌ | ❌ | ✅ |

## 7. Location Abstraction by Continent/Island Grouping
| Region Group | Countries Included | Example Travel Concept |
|---|---|---|
| Eastern Europe Exclusion Belt | Ukraine, Poland | rail convoy/checkpoint travel |
| Pacific Rupture Arc | Japan + maritime links | ferry/submarine passage |
| Americas Fracture Zone | United States, Brazil | long-range cargo routes |
| Southern Resource Corridor | South Africa + sea links | escort caravan contracts |

## 8. City Actions (Post-Nuclear Examples)
| City | Example Actions |
|---|---|
| Pripyat | Reactor scavenging, radiation sampling, mutant nest clearing |
| Kyiv | District patrols, shelter repair, black-market barter |
| Warsaw | Defensive wall building, ration cooking, ammo crafting |
| Gdansk | Harbor fishing, ship salvage, smuggling interception |
| Detroit | Factory salvage, weapon assembly, gang territory fights |
| Seattle | Rainwater purification, dock trading, coastal hunting |
| Tokyo | Drone-part scavenging, tech crafting, secure-sector contracts |
| Sapporo | Cold-weather hunting, fuel management, bunker maintenance |
| Rio de Janeiro | Urban foraging, faction diplomacy, rooftop farming |
| Manaus | Jungle hunting tiers, herbal medicine crafting, river trade |
| Johannesburg | Mine salvage, convoy defense, armor crafting |
| Cape Town | Coastal farming, fish processing, settlement construction |

Action families:
- Gathering: scavenging, logging, mining, foraging
- Production: cooking, crafting, engineering, medicine
- Building: shelter upgrades, walls, generators, traps
- Social: chat, proximity chat, trading, faction contracts
- Combat: PvE hunts, PvP duels, defense events
- Exploration: city travel, district expeditions

## 9. Premium Benefits (Cosmetic Only)
- Exclusive outfits/skins
- Profile flair and badge cosmetics
- Additional UI themes/layout color schemes
- Cosmetic emotes
- Expanded cosmetic loadout slots

Constraint: no pay-to-win bonuses (no combat/resource/stat boosts).

## 10. Suggested Screen Data Composition
1. `users` + `roles` (permissions)
2. `users` + `user_locations` + `cities` + `countries` (current location)
3. `cities` + `city_actions` (+ skill requirements) (available actions)
4. Optional overlays: weather modifiers, danger/trouble, local events

## 11. Additional Considerations
1. Should premium be modeled as role or as subscription layered over base role?
2. What is the PvP policy (always-on, opt-in, event-based)?
3. Are weather/trouble values static seeds only, or dynamic over time?
4. Should moderators get audit visibility for role changes without change rights?
5. Is city travel instant, timed, or resource-gated?
6. Should guests see public chat or only curated announcements?
7. Which realtime features are required for v1 (global chat only vs full proximity/trade channels)?
