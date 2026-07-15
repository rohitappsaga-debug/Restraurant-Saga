# Restaurant Saga — Mobile App & API Plan

> Created: 2026-07-15 · Updated: 2026-07-15
> Goal: Build a mobile app (waiter / kitchen / admin) for Restaurant Saga, backed by a proper Laravel REST API.

## STATUS

- ✅ **DONE (2026-07-15):** Full API surface implemented and verified. `/api/v1` (94 routes), auth (login/logout/me/**profile update**/change-password, device-named Sanctum tokens), API Resources, Form Requests, role-gated endpoints for the entire web feature set:
  - *Waiter:* tables (list/show/open-order/clean/**status**), order lifecycle (create/add-items/serve/serve-all/bill/discount/pay/cancel/**hold**), reservations.
  - *Kitchen:* KOT queue, item status, **order dismiss/force-close**, 86'ing items.
  - *Admin/Manager:* dashboard, **sales report + ranged analytics** (trend/category-split/top-items), payments, activity logs, **settings GET/PATCH**, menu CRUD + **image upload + AI generation** + **modifier CRUD**, table CRUD + **bulk create + grouping**, suppliers, ingredients (+stock adjust), recipes, **purchase orders with line items + receiving→stock**, users.
  - *Shared:* settings read (all roles), notifications, FCM device registration (`device_tokens` table).
  - **114 feature tests pass** (346 assertions). All broadcasts are fail-safe (a down Reverb server never blocks an order). Smoke-tested end-to-end over HTTP.
- ⏳ **NEXT:** Scribe API docs, FCM push *sending* (tokens are stored, sender not wired), then the Flutter app (Phase 4).

---

## 1. Current State (what already exists)

- **Backend:** Laravel 13 + Livewire 4 web app, PostgreSQL, Docker deployment.
- **Auth:** Laravel Sanctum 4 is installed. `POST /api/auth/login` already issues personal access tokens (`AuthService::login`).
- **Existing API routes** (`routes/api.php`):
  - `POST /auth/login`
  - `GET/POST /orders`, `PATCH /orders/{id}/status` (full controllers with Form Requests + `OrderService`)
  - `apiResource` stubs for: categories, menu-items, tables, reservations, notifications, recipes, payments
  - Admin/manager-only stubs: suppliers, ingredients, reports, users, activity-logs, purchase-orders
- **Domain already built** (services power the Livewire UI, so business logic is reusable):
  - Order-first flow with multi-table support (`order_table` pivot), split orders (`parent_order_id`), hold status
  - KOT workflow (`Kot` model + `KOTService`), kitchen dashboard
  - Billing (`BillingService`), payments (cash/card/UPI), discounts, service charge
  - Inventory (ingredients, recipes, suppliers, purchase orders), reservations, reports, notifications, audit logs
- **Roles:** `admin`, `manager`, `waiter`, `kitchen`, `delivery` (`UserRole` enum + `role` middleware)

### Gaps to close before a mobile app can ship

| Gap | Detail |
|---|---|
| No logout / me / token mgmt | Only login exists; no token revocation or profile endpoint |
| Stub controllers unsafe | 13-line stubs pass `$request->all()` straight to services — no validation, no pagination, no authorization |
| No API Resources | Raw Eloquent models are serialized (leaks columns, unstable contract) |
| No versioning | Routes live at `/api/*`, not `/api/v1/*` |
| Inconsistent responses | Orders wrap in `{success, data}`, stubs return bare JSON |
| No KOT / billing endpoints | Kitchen flow and bill settlement have no API surface |
| No real-time | Kitchen/waiter need live order updates (currently Livewire polling only) |
| No push notifications | No FCM integration |
| No JSON error handling | 401/403/422 may return HTML redirects for API clients |
| No rate limiting / CORS config for mobile | Needed for production |

---

## 2. Phase 1 — API Foundation (Laravel)

1. **Versioning:** move everything to `routes/api.php` under a `v1` prefix → `/api/v1/...`.
2. **Standard response envelope** via a base `ApiController` or helper:
   ```json
   { "success": true, "message": "...", "data": {...}, "pagination": {...} }
   ```
3. **JSON exception rendering** in `bootstrap/app.php`: 401, 403, 404, 422 always return JSON for `api/*` routes.
4. **Auth endpoints:**
   - `POST /v1/auth/login` — accept `device_name`; return token + user + role
   - `POST /v1/auth/logout` — revoke current token
   - `GET  /v1/auth/me` — profile + role + theme/notification prefs
   - `POST /v1/auth/change-password`
5. **Sanctum token abilities** mapped from role (e.g. `orders:create`, `kot:update`) or keep `role:` middleware — recommended: keep existing `role` middleware, add per-route policies later.
6. **API Resources** for every model exposed (OrderResource with `table_label`, items, kots; MenuItemResource with image URL; etc.).
7. **Form Requests** for all stub controllers (replace `$request->all()`).
8. **Rate limiting:** `throttle:60,1` default, `throttle:5,1` on login.
9. **CORS + Sanctum config** for mobile clients (token-based, no cookies needed).

## 3. Phase 2 — Endpoint Coverage by Role

### Waiter app
- `GET /v1/tables` (with live status), `GET /v1/tables/{id}/open-order`
- `GET /v1/categories`, `GET /v1/menu-items?category=&search=&available=1`
- `POST /v1/orders` (dine-in multi-table / takeaway / delivery), `PATCH /v1/orders/{id}` (add items → new KOT), `PATCH /v1/orders/{id}/status`
- `POST /v1/orders/{id}/hold`, `POST /v1/orders/{id}/split`, `POST /v1/orders/{id}/cancel` (with reason)
- `POST /v1/orders/{id}/bill` (apply discount/service charge), `POST /v1/orders/{id}/payments` (cash/card/UPI, settle)
- `GET/POST /v1/reservations`, check-in

### Kitchen app
- `GET /v1/kots?status=pending|preparing` (live queue)
- `PATCH /v1/kots/{id}/status` (accept → preparing → ready)
- Item-level ready marking if needed: `PATCH /v1/kot-items/{id}/status`

### Admin / Manager app
- Dashboard summary: `GET /v1/reports/dashboard` (today's sales, open orders, top items)
- Reports: `GET /v1/reports/sales?from=&to=`, daily sales
- Menu management (CRUD + image upload `POST /v1/menu-items/{id}/image`)
- Users, suppliers, ingredients, purchase orders, activity/audit logs, settings

### Shared
- `GET /v1/notifications`, `PATCH /v1/notifications/{id}/read`, `POST /v1/devices` (register FCM token)

## 4. Phase 3 — Real-time & Push

1. **Laravel Reverb** (first-party WebSockets) + `laravel-echo` on mobile:
   - Channels: `kitchen` (new KOTs), `waiter.{userId}` / `tables` (KOT ready, table status), `admin.orders` (live orders)
   - Broadcast events from existing services: `OrderCreated`, `KotStatusUpdated`, `OrderPaid`, `TableStatusChanged`
2. **FCM push** (via `kreait/laravel-firebase` or `laravel-notification-channels/fcm`) for backgrounded apps: KOT ready, new order, reservation reminders.
3. Fallback: 10–15s polling endpoints already work if WebSockets are deferred to a later release.

## 5. Phase 4 — Mobile App

- **Stack recommendation: Flutter** — single codebase for Android + iOS, strong offline/local-state story, good for tablet (kitchen display) + phone (waiter). Alternative: React Native if the team prefers JS.
- **One app, role-based routing** after login (waiter UI / kitchen UI / admin UI), rather than 3 separate apps — simpler distribution and shared code.
- **Structure (Flutter):**
  - `dio` + interceptor (attach Sanctum token, refresh on 401 → force re-login)
  - `riverpod` (state) + `freezed` models mirroring API Resources
  - `flutter_secure_storage` for token
  - `firebase_messaging` for push; `web_socket_channel`/echo client for Reverb
- **Key screens:**
  - Login → role detection
  - Waiter: table grid (status colors), menu/cart order builder, order detail (KOTs, statuses), billing/settle, reservations
  - Kitchen: KOT queue board (pending/preparing/ready columns), tap-to-advance
  - Admin: dashboard KPIs, live orders, reports, menu & user management
- **Offline handling:** cache menu/categories locally; queue order submissions is *not* attempted in v1 (require connectivity for order ops).

## 6. Phase 5 — Testing, Docs, Delivery

1. **Feature tests** (existing test suite pattern) for every v1 endpoint: auth, role gates, validation, happy paths for order → KOT → bill → payment lifecycle.
2. **API docs:** Scribe (`knuckleswtf/scribe`) auto-generated OpenAPI + Postman collection.
3. **CI:** run tests in existing Docker/Make setup.
4. **Release:** staging API URL config via app flavors/env; Play Store internal track + TestFlight.

## 7. Suggested Order of Work

| # | Milestone | Est. |
|---|---|---|
| 1 | API foundation (versioning, auth endpoints, error/JSON handling, resources) | 3–4 days |
| 2 | Waiter endpoints (tables, menu, orders, KOT creation, billing/payments) | 4–5 days |
| 3 | Kitchen endpoints (KOT queue + status) | 1–2 days |
| 4 | Admin endpoints (dashboard, reports, CRUD hardening) | 3–4 days |
| 5 | Tests + Scribe docs | 2–3 days |
| 6 | Flutter app shell: auth, theming, role routing | 3 days |
| 7 | Waiter module | 5–6 days |
| 8 | Kitchen module | 2–3 days |
| 9 | Admin module | 4–5 days |
| 10 | Reverb real-time + FCM push | 3–4 days |
| 11 | QA, beta, store submission | 1 week |

**Total rough estimate: ~6–8 weeks** for a single developer, v1 scope.

## 8. Open Decisions

- Flutter vs React Native (recommendation: Flutter)
- One combined app vs separate waiter/kitchen apps (recommendation: one app, role-routed)
- Real-time in v1 vs polling first, Reverb in v1.1 (recommendation: polling first if timeline is tight)
- Delivery-role app scope (driver tracking) — defer to v2
- Customer-facing ordering app — out of scope for this plan (v2+)
