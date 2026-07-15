# Restaurant Saga Mobile — Architecture Guide

> Companion to [FLUTTER_APP_PLAN.md](FLUTTER_APP_PLAN.md). This document defines **how the code is structured** so every feature is built the same way. Phase 2 (the API layer wired to `/api/v1`) is implemented under `mobile/lib/data/`.

---

## 1. Architectural style: layered + feature-first

We use a pragmatic **3-layer clean architecture**, organized **feature-first**. Dependencies point in one direction only:

```
   ┌───────────────────────────────────────────────┐
   │  PRESENTATION  (features/*/presentation)        │  Widgets, screens, controllers (Riverpod)
   │      depends on ↓                               │
   ├───────────────────────────────────────────────┤
   │  DOMAIN        (features/*/domain, core/domain)  │  Repository interfaces, entities, value types
   │      depends on ↓ (nothing else)                │
   ├───────────────────────────────────────────────┤
   │  DATA          (data/*)                          │  API client, DTO models, repository IMPLEMENTATIONS
   └───────────────────────────────────────────────┘
```

**The rule that makes it work:** presentation and domain never import `dio`, never know a URL, never see JSON. They depend only on **abstract repository interfaces** and **models**. The `data` layer is the only place that talks HTTP. This is what lets us build the whole UI on a `MockRepository` and later swap in `ApiRepository` by changing one provider.

### Why this shape
- **Testable:** controllers take a repository interface → trivial to inject a fake in tests.
- **Swappable backend:** mock ⇆ live is one line (see §6).
- **Parallelizable:** UI work (phase 1) and API work (phase 2) proceed independently against the shared interface.

---

## 2. Folder structure

```
mobile/lib/
├── main.dart                     # runApp(ProviderScope(child: RestaurantSagaApp()))
├── app.dart                      # MaterialApp.router, theme, router wiring
│
├── core/                         # cross-cutting, feature-agnostic
│   ├── env/app_config.dart       # base URL per flavor
│   ├── network/
│   │   ├── api_client.dart       # dio wrapper; unwraps {success,message,data,pagination}
│   │   ├── api_response.dart     # envelope value object
│   │   ├── api_exception.dart    # typed errors (network/unauth/validation/server)
│   │   ├── pagination.dart       # Paginated<T>
│   │   └── interceptors/         # auth token, error mapping, logging
│   ├── storage/token_storage.dart# secure token at rest
│   ├── result/result.dart        # Result<T> = Ok | Err  (no exceptions across layers)
│   ├── theme/                     # AppTheme, colors, spacing, typography
│   ├── layout/                    # Breakpoints, AdaptiveScaffold
│   └── router/                    # go_router config + auth guard
│
├── data/                         # ← PHASE 2 lives here
│   ├── models/                   # immutable DTOs with fromJson (no codegen)
│   └── repositories/
│       ├── *_repository.dart          # abstract interface (domain-facing)
│       └── api/*_api_repository.dart   # HTTP implementation
│
├── providers/                    # Riverpod DI graph (wires client → repos → controllers)
│
└── features/                     # feature-first; each mirrors a role area
    ├── auth/        presentation/{login_screen, splash_screen} + controller
    ├── waiter/      presentation/{tables, order, bill, reservations}
    ├── kitchen/     presentation/{board, menu}
    ├── admin/       presentation/{dashboard, menu, tables, inventory, reports, users, settings}
    └── shared/      presentation/{profile, notifications}
```

**Feature-first, not layer-first:** everything for "waiter tables" lives together. Shared building blocks live in `core/` and `data/`.

---

## 3. The data layer (phase 2 in detail)

### 3.1 Envelope contract
Every `/api/v1` response is `{ "success": bool, "message": string?, "data": ..., "pagination": {...}? }`. The `ApiClient` is the single place that understands this. It returns an `ApiResponse`:

```dart
class ApiResponse {
  final dynamic data;          // Map, List, or null
  final String? message;
  final Pagination? pagination;
}
```

Repositories receive `ApiResponse`, read `.data`, and map it to models with `Model.fromJson`. Nothing above the data layer ever sees `success`/envelope keys.

### 3.2 Models (DTOs)
- Immutable classes, `final` fields, `const` constructors, a `fromJson` factory. **No `build_runner`** — the project compiles immediately after `flutter pub get`.
- Enums (`OrderStatus`, `TableStatus`, `PaymentMethod`, `UserRole`) mirror the Laravel enums exactly and parse from their string `value`.
- Request bodies are built as `Map<String,dynamic>` inside repository methods from typed parameters — models stay read-only, so we don't carry serialization we don't need.
- Migrating to `freezed` later is mechanical and optional; the guide deliberately stays codegen-free to keep the toolchain simple.

### 3.3 Repository pattern
- **Interface** (`data/repositories/order_repository.dart`) — pure Dart, returns models/`Result`, no `dio`.
- **Implementation** (`data/repositories/api/order_api_repository.dart`) — takes an `ApiClient`, builds paths/bodies, maps responses.
- One repository per resource group. Endpoint→method mapping is documented in [FLUTTER_APP_PLAN.md §5](FLUTTER_APP_PLAN.md) and in each interface's doc comments.

### 3.4 Error handling — `Result<T>` at the boundary
The `ApiClient` throws typed `ApiException`s internally (from a dio error interceptor). Repositories **catch** these and return a `Result<T>` (`Ok(value)` | `Err(failure)`) so the UI never wraps calls in try/catch:

```dart
final res = await ref.read(orderRepositoryProvider).createOrder(...);
res.when(
  ok: (order) => _goToBill(order),
  err: (f) => showError(f.message),        // f.type ∈ {network, unauthorized, validation, server}
);
```

`validation` failures carry the field errors map from Laravel's 422 so forms can highlight fields.

### 3.5 Auth & the token lifecycle
- On login, `AuthRepository.login()` returns `{user, token}`; the controller stores the token via `TokenStorage` (flutter_secure_storage).
- An `AuthInterceptor` attaches `Authorization: Bearer <token>` to every request.
- On `401`, the interceptor clears the token and the router guard bounces to `/login` (Sanctum tokens don't refresh; re-login is the correct flow).
- `device_name` is sent at login; the FCM `device_token` is registered via `DeviceRepository` after permission grant and cleared on logout.

---

## 4. State management (Riverpod)

- **DI:** providers in `providers/` expose the `ApiClient`, each repository, and controllers. Swapping mock⇆api happens here.
- **Screen state:** a `StateNotifier`/`AsyncNotifier` per screen (`*Controller`) holds `AsyncValue<T>` (loading/data/error) so every screen renders the four canonical states uniformly.
- **No business logic in widgets.** Widgets read a controller and render; they call controller methods on interaction. Controllers call repositories.
- Plain Riverpod (no `riverpod_generator`) — again, codegen-free.

```
Widget → ref.watch(xController) → Controller → Repository(interface) → ApiRepository → ApiClient → dio → /api/v1
                                                     ↑ (mock swap point)
```

---

## 5. Navigation & adaptivity

- `go_router` with a `ShellRoute` per role; a `redirect` guard reads auth state.
- `AdaptiveScaffold` (in `core/layout/`) switches bottom-nav ⇆ rail ⇆ two-pane on `Breakpoints` (compact <600, medium 600–839, expanded ≥840). See plan §2.
- Screens are written **layout-agnostic**: they expose a "list" and a "detail" widget; `AdaptiveScaffold` decides whether detail is a pushed page (phone) or a right pane (tablet/iPad).

---

## 6. The mock ⇆ live swap (why the architecture pays off)

`providers/repository_providers.dart`:

```dart
// Phase 1 (UI on mock data):
final orderRepositoryProvider = Provider<OrderRepository>((ref) => MockOrderRepository());

// Phase 2 (live API) — flip to:
final orderRepositoryProvider =
    Provider<OrderRepository>((ref) => OrderApiRepository(ref.read(apiClientProvider)));
```

Nothing in `features/` changes. This is the single most important property of the design.

---

## 7. Conventions

- **Naming:** files `snake_case.dart`; classes `PascalCase`; providers end in `Provider`; controllers end in `Controller`; API repos end in `ApiRepository`.
- **Immutability:** models and state objects are immutable; use `copyWith` for updates.
- **No magic strings for routes/keys:** centralize in `core/router/routes.dart`.
- **Money & dates:** format via `core/utils` using the currency from `/settings` — never hard-code `₹`.
- **Every screen ships 4 states:** loading (shimmer), empty, error (with retry), data.
- **Testing:** unit-test repositories against a fake `ApiClient`; widget-test screens against a fake repository. Domain has zero Flutter imports.

---

## 8. Dependency choices (codegen-free, runnable on `flutter pub get`)

| Concern | Package |
|---|---|
| State / DI | `flutter_riverpod` |
| Routing | `go_router` |
| HTTP | `dio` |
| Secure token | `flutter_secure_storage` |
| Prefs (theme) | `shared_preferences` |
| Images | `cached_network_image` |
| Charts (admin) | `fl_chart` |
| Real-time (later) | `pusher_channels_flutter` |
| Push (later) | `firebase_messaging` |

Deliberately **no** `freezed`/`json_serializable`/`riverpod_generator` in the base setup, to keep the build simple and the delivered code immediately compilable. Teams that prefer codegen can add it without changing the layering.

---

## 9. Where phase 2 is implemented

`mobile/lib/data/` contains, for every resource group we built on the backend:
- a model (`data/models/…`),
- a repository interface (`data/repositories/…_repository.dart`),
- an HTTP implementation (`data/repositories/api/…_api_repository.dart`).

`mobile/lib/providers/` wires them to the `ApiClient`. `features/auth/` demonstrates the full path end-to-end (login → token stored → authed requests → role routing). Remaining features consume the same repositories when their phase-1 UI is built.
