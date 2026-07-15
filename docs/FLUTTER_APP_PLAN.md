# Restaurant Saga — Flutter Mobile App Plan (UI-First)

> Created: 2026-07-15 · Updated: 2026-07-15
> Goal: One Flutter codebase for the whole Restaurant Saga app — waiter, kitchen, and admin/manager — running on **Android phones, iPhones, Android tablets, and iPads**.
> Approach: **Build the complete UI first** (all screens, navigation, theming, adaptive layouts) against mock data, then wire it to the live `/api/v1` backend.

## STATUS

- Architecture guide: [ARCHITECTURE.md](ARCHITECTURE.md).
- ✅ **Phase 2 (API integration) implemented** in `mobile/` — a codegen-free Flutter project: dio `ApiClient` (envelope unwrap + typed errors + Sanctum auth interceptor), hand-written models for every API Resource, and **19 repository interfaces + 19 API implementations covering all 94 `/api/v1` endpoints**, wired via Riverpod DI. Data/domain layers are Flutter-free; `dio` is confined to `core/network/`. A working login authenticates and routes by role; waiter/kitchen/admin home screens already read live data (tables / KOT queue / dashboard).
- ⏳ **NEXT:** Phase-1 feature UIs (§5) consuming these repositories; then Reverb real-time + FCM; then store prep. (Note: UI-first order was inverted at the user's request — the API layer landed first; feature UIs slot on top of the ready repositories.)

---

## 0. Guiding principles

1. **UI-first.** Every screen is built and clickable with in-memory mock data before any HTTP call exists. This lets us review the whole app visually and lock the design before backend wiring. A single `AppRepository` interface has two implementations: `MockRepository` (phase 1) and `ApiRepository` (phase 2). Swapping one line switches the whole app from mock to live.
2. **One app, role-routed.** After login the app reads the user's `role` and shows the waiter, kitchen, or admin shell. No separate apps to maintain.
3. **Adaptive, not just responsive.** Phone and tablet get genuinely different layouts (single-column vs master-detail), not a stretched phone UI. This is the core requirement for iPad/tablet support.
4. **Design parity with the web app.** Same brand color, light/dark theme, currency symbol, and terminology (KOT, cover, table, bill) so staff moving between web and mobile feel at home.

---

## 1. Tech stack

| Concern | Choice | Why |
|---|---|---|
| Framework | **Flutter (stable)**, Dart 3 | Single codebase → Android + iOS + tablets + iPad; excellent adaptive-layout support |
| State management | **Riverpod** (v2, code-gen) | Testable, compile-safe, great for the mock→api swap |
| Navigation | **go_router** | Declarative, deep-linkable, supports nested shells per role |
| Models / JSON | **freezed** + **json_serializable** | Immutable models mirroring the API Resources; `fromJson` for free |
| HTTP (phase 2) | **dio** + interceptors | Token attach, 401 refresh-to-login, logging, retry |
| Local storage | **flutter_secure_storage** (token), **shared_preferences** (theme/prefs) | Secure token at rest |
| Real-time (later) | **pusher_channels_flutter** / laravel-echo client | Connects to Laravel Reverb for live KOT/table updates |
| Push (later) | **firebase_messaging** | Consumes the `device_tokens` endpoints already built |
| Charts (admin) | **fl_chart** | Sales trend, category split, top items |
| Images | **cached_network_image** | Dish thumbnails |

### Adaptive helpers
- `flutter_adaptive_scaffold` (or a hand-rolled `AdaptiveLayout`) for the phone/tablet navigation switch.
- `LayoutBuilder` + a `Breakpoints` constants file everywhere a screen changes shape.

---

## 2. Device & form-factor strategy

Support the full matrix from **one** codebase using width breakpoints, not device checks.

| Breakpoint | Width | Target devices | Navigation | Content |
|---|---|---|---|---|
| `compact` | < 600 dp | phones (portrait) | Bottom nav bar | Single column; drill-down pushes a new page |
| `medium` | 600–839 dp | small tablets, phones (landscape), iPad mini portrait | Navigation rail | Single wide column or 2-pane where it helps |
| `expanded` | ≥ 840 dp | iPad, Android tablets, iPad Pro | Navigation rail / drawer | **Master-detail** two-pane (list on left, detail on right) |

**Concrete adaptive behaviors:**
- **Waiter — tables:** phone shows a scrollable grid; tablet shows the table grid on the left and the selected order/cart on the right (no page transition).
- **Waiter — menu/cart:** phone = menu screen → cart screen (separate); tablet = menu on the left, live cart panel pinned on the right.
- **Kitchen — KOT board:** phone = one status column at a time with tabs (Pending / Preparing / Ready); tablet = all columns side-by-side like a real kitchen display (KDS).
- **Admin — every list:** phone = list → detail page; tablet = list + detail two-pane.
- **Orientation:** all screens support portrait and landscape; kitchen board defaults to landscape on tablets.
- **Safe areas & notches:** wrap scaffolds in `SafeArea`; test on notched iPhones and iPad home-indicator.
- **Text scaling:** respect OS font-size settings; layouts use `Flexible`/`Wrap`, never fixed heights for text rows.

---

## 3. Design system

Build this **first** — everything else consumes it.

- **Theme:** `AppTheme.light` / `AppTheme.dark` via `ThemeData` + a `ColorScheme` seeded from the web app's brand color. Honors the user's `theme` field from `/auth/me`; also follows system theme by default.
- **Tokens:** spacing scale (4/8/12/16/24/32), radius, elevation, typography ramp (display → body → caption).
- **Reusable widgets** (`lib/ui/components/`):
  - `AppButton` (primary/secondary/danger, loading state)
  - `AppCard`, `SectionHeader`, `EmptyState`, `ErrorState`, `LoadingShimmer`
  - `StatusChip` (order/table/KOT status → color)
  - `TableTile` (number, cover count, status color, "ready" badge)
  - `MenuItemCard` (image, name, price, veg/non-veg dot, availability)
  - `CartLineRow` (qty stepper, modifiers, notes, line total)
  - `QuantityStepper`, `MoneyText` (uses currency from `/settings`)
  - `KotCard` (batch #, table, items, elapsed timer)
  - `StatTile` (KPI value + trend arrow), `AppDataTable` (admin lists)
  - `AdaptiveScaffold` wrapper (bottom-nav ⇆ rail ⇆ two-pane)
- **Feedback:** toast/snackbar service, confirm-dialog service, bottom-sheet service — all theme-aware.

---

## 4. Navigation architecture

```
AppRouter (go_router)
├── /splash                 (bootstraps: token check, load settings)
├── /login                  (shared login; routes by role after auth)
├── /waiter        (ShellRoute → bottom nav / rail)
│   ├── /waiter/tables
│   ├── /waiter/order/:id   (menu + cart, adaptive)
│   ├── /waiter/bill/:id
│   ├── /waiter/reservations
│   └── /waiter/profile
├── /kitchen       (ShellRoute)
│   ├── /kitchen/board      (KOT columns)
│   └── /kitchen/menu       (86 / availability)
└── /admin         (ShellRoute → rail / drawer)
    ├── /admin/dashboard
    ├── /admin/orders
    ├── /admin/menu
    ├── /admin/tables
    ├── /admin/reservations
    ├── /admin/inventory    (ingredients, suppliers, POs)
    ├── /admin/reports
    ├── /admin/users
    ├── /admin/settings
    └── /admin/activity
```

`redirect` guard: unauthenticated → `/login`; wrong-role deep link → that user's home.

---

## 5. Screen inventory (the "whole application")

Each screen lists the API endpoint(s) it will bind to in phase 2 (all already built under `/api/v1`).

### Shared
- **Splash** — token bootstrap, `GET /settings`, `GET /auth/me`.
- **Login** — `POST /auth/login` (sends `device_name`); routes by `role`.
- **Profile / Preferences** — `GET /auth/me`, `PATCH /auth/profile` (theme, notifications), `POST /auth/change-password`, `POST /auth/logout`.
- **Notifications** — `GET /notifications`, `PATCH /notifications/{id}/read`, `POST /notifications/read-all`.

### Waiter
- **Tables** — grid with status colors + "ready items" badge (`GET /tables`); tap free tables to multi-select, tap occupied to open its bill (`GET /tables/{id}/open-order`); mark cleaned (`POST /tables/{id}/clean`).
- **Order builder** — categories + menu (`GET /categories`, `GET /menu-items?...`), cart with modifiers/notes/qty, send to kitchen (`POST /orders`, `POST /orders/{id}/items`).
- **Order / bill detail** — item statuses, serve item / serve-all (`PATCH .../serve`, `POST .../serve-all`), discount (`POST .../discount`), hold (`POST .../hold`), cancel (`POST .../cancel`), bill totals (`GET .../bill`), take payment (`POST .../payments`).
- **Reservations** — list/create/check-in (`GET/POST /reservations`, `POST .../check-in`).

### Kitchen
- **KOT board** — live queue as status columns (`GET /kitchen/queue`); tap item to advance preparing→ready (`PATCH /kitchen/items/{id}/status`); dismiss / force-close order (`POST /kitchen/orders/{id}/dismiss|force-close`).
- **Menu availability** — toggle 86 (`POST /menu-items/{id}/toggle-availability`).

### Admin / Manager
- **Dashboard** — KPI tiles + top items today (`GET /dashboard`).
- **Live orders** — filter/search (`GET /orders`), detail, status.
- **Menu management** — CRUD (`.../menu-items`), image upload + AI generate (`POST .../image`, `.../generate-image`), modifier CRUD (`.../modifiers`), categories CRUD.
- **Tables** — CRUD, bulk create, grouping, status (`POST /tables/bulk|group|ungroup`, `PATCH /tables/{id}/status`).
- **Inventory** — ingredients (+ stock adjust), suppliers, purchase orders with line items + receive (`.../purchase-orders/{id}/items|receive`).
- **Reports** — sales + ranged analytics with charts (`GET /reports/sales`, `/reports/analytics`).
- **Users** — CRUD (`.../users`).
- **Settings** — `GET/PATCH /settings` (tax, currency, payment methods, discount presets, receipt footer, business hours).
- **Activity log** — `GET /activity-logs`.

---

## 6. Project structure

```
lib/
├── main.dart
├── app.dart                      # MaterialApp.router + theme
├── core/
│   ├── theme/                     # AppTheme, colors, typography, spacing
│   ├── router/                    # go_router config + guards
│   ├── layout/                    # AdaptiveScaffold, Breakpoints
│   ├── env/                       # base URL per flavor
│   └── utils/                     # formatters, money, date
├── ui/
│   └── components/                # shared widgets (Section 3)
├── features/
│   ├── auth/        { view/  widgets/  controller/  }
│   ├── waiter/      { tables/ order/ bill/ reservations/ }
│   ├── kitchen/     { board/ menu/ }
│   ├── admin/       { dashboard/ menu/ tables/ inventory/ reports/ users/ settings/ }
│   └── shared/      { notifications/ profile/ }
├── data/
│   ├── models/                    # freezed models mirroring API Resources
│   ├── repositories/              # AppRepository (abstract)
│   ├── mock/                      # MockRepository + fixtures  ← phase 1
│   └── api/                       # ApiRepository + dio client ← phase 2
└── l10n/                          # strings (currency/labels)
```

---

## 7. Milestones (UI-first)

| # | Milestone | Output | Est. |
|---|---|---|---|
| 1 | **Project + design system** | Flutter app boots on all 4 form factors; theme, tokens, core components, `AdaptiveScaffold` | 3–4 days |
| 2 | **Navigation + mock data layer** | go_router shells per role, `MockRepository` with realistic fixtures, login screen routes by role | 2–3 days |
| 3 | **Waiter UI** | Tables (adaptive grid/two-pane), order builder, bill, reservations — fully clickable on mock data | 5–6 days |
| 4 | **Kitchen UI** | KOT board (phone tabs ⇆ tablet columns), menu availability | 3 days |
| 5 | **Admin UI** | Dashboard, orders, menu, tables, inventory, reports (charts), users, settings, activity | 6–8 days |
| 6 | **UI review & polish** | Empty/loading/error states everywhere; portrait+landscape; light/dark; accessibility & text-scaling pass on iPhone/iPad/Android phone/tablet | 3–4 days |
| — | **← UI COMPLETE (reviewable app on mock data) →** | | |
| 7 | **API wiring** | `ApiRepository` (dio + Sanctum token), swap from mock, error handling, pagination | 5–6 days |
| 8 | **Real-time + push** | Reverb live updates (KOT/tables), FCM device registration + notifications | 3–4 days |
| 9 | **QA + store prep** | Device-lab testing, app icons/splash, flavors (staging/prod), TestFlight + Play internal | 1 week |

**UI-first phase (1–6): ~3–4 weeks.** Full app to store: ~7–9 weeks.

---

## 8. Definition of done for "UI complete" (end of milestone 6)

- Every screen in Section 5 exists and is navigable on mock data.
- Runs and looks correct on: **iPhone, Android phone, iPad, Android tablet**, in **portrait and landscape**, in **light and dark**.
- Phone uses bottom-nav + drill-down; tablet/iPad uses rail + master-detail; kitchen board shows columns on tablet.
- All interactive states present: loading (shimmer), empty, error, success toasts, confirm dialogs.
- No hard-coded pixel layouts that break on text scaling or small tablets.
- Currency, labels, and theme match the web app.

---

## 9. Open decisions (defaults chosen; change if needed)

- **State mgmt:** Riverpod (recommended) vs Bloc — default Riverpod.
- **Min OS:** Android 8 (API 26) / iOS 14 — covers virtually all restaurant hardware.
- **Offline:** read-only caching of menu/tables in v1; **no** offline order queueing (order ops require connectivity).
- **Kitchen display on a wall tablet:** treat as the `expanded` breakpoint; add a "keep screen awake" + auto-refresh mode.
- **Delivery role:** UI stubs only in v1; full driver flow deferred to v2 (matches API plan).
