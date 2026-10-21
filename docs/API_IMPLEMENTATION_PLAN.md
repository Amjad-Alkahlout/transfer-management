# API Implementation Plan

## Objective

Add a versioned JSON API for the existing money-transfer application, suitable for first-party mobile clients. Preserve the current Livewire web experience and reuse the existing domain services for financial operations.

This document is the implementation sequence and scope. Work through the phases in order. Run that phase's tests immediately after completing it, fix failures caused by the phase, and do not proceed until its checks pass or a pre-existing/environmental failure is clearly identified and reported. Do not skip ahead or add endpoints outside this scope without updating the plan first.

## Progress

- **Phase 1 — Complete.** Added Sanctum bearer-token login/logout and `/me`, active-user enforcement, API route registration, 30-day token expiry, and feature tests. Phase tests passed (7 tests, 34 assertions). The full suite reported 8/9 passing; the remaining existing `ExampleTest` expects `/` to return `200`, while the current web route intentionally redirects to login (`302`).
- **Phase 2 — Complete.** Added paginated/filterable transfer reads, transfer details with payment history, calculation for both existing modes, dashboard summary, and exchange-rate reads with role checks and currency-safe decimal strings. Phase tests passed (9 tests, 55 assertions); all API v1 tests pass together (16 tests, 89 assertions).
- **Phase 3 — Complete.** Extracted shared transfer create/update operations for API and Livewire, added API create/update endpoints and validation, and recorded creator/payment actors explicitly. Tests passed (9 transfer mutation tests); all API v1 tests pass together (25 tests, 130 assertions).
- **Phase 4 — Complete.** Added idempotent installment payments, transfer-row locking for payment/execution/cancellation transitions, proof uploads, lifecycle endpoints, explicit actor IDs, and existing event dispatch from shared services. Phase tests passed (9 tests, 65 assertions); all API v1 tests pass together (34 tests, 195 assertions).
- **Phase 5 — Complete.** Added capital account management, audited opening balances, manual adjustments, profit withdrawals, exchange-rate updates, capital-transfer preview/execution/history, admin ledger reads, and safe admin user management. Capital adjustments, profit withdrawals, and capital transfers require idempotency keys. Shared services record actor IDs and preserve ledger effects. Phase tests passed (8 tests, 56 assertions); all API v1 tests pass together (42 tests, 251 assertions).
- **Phase 6 — Complete.** Added `docs/openapi.json` for all 30 API routes and contract tests that check JSON, component references, and route/method coverage. API v1 tests passed (44 tests, 1,082 assertions); the full suite passed 45/46 tests. The lone failure is the existing `ExampleTest` expectation that `/` returns 200; the current web route redirects unauthenticated requests to login (302). Pint passes on API implementation files. Project-wide `pint --test` still reports existing style differences in unrelated files, which were left untouched.

## Current project findings

- The project is Laravel and Livewire, with the current application routes in `routes/web.php`.
- `bootstrap/app.php` does not currently register `routes/api.php`.
- The existing login flow and single-device middleware are session-oriented. There is no API token authentication configured.
- Role checks are defined as Laravel Gates in `AppServiceProvider` and also appear inside Livewire actions.
- Transfer calculation, payment receipt, execution, cancellation, capital movement, profit withdrawal, and force-cancellation logic already exists in services. API handlers should delegate to these services instead of implementing ledger changes themselves.
- Some services read the current actor through `auth()->id()`. API use requires explicit actor identity to be passed into those service operations.
- Transfer creation, account creation, exchange-rate editing, and parts of financial administration currently live in Livewire components rather than reusable application services.
- Several financial operations use `float`. API input/output must use decimal validation and two-decimal serialization; financial arithmetic precision needs review before implementation.
- `bootstrap/app.php` already configures JSON exception rendering for requests under `api/*`.
- The working tree had pre-existing edits in Composer files, language files, and Livewire views at review time. Preserve those changes and do not revert or overwrite them.

## API conventions

- Prefix all endpoints with `/api/v1`.
- Use JSON request and response bodies, except multipart upload for execution proof.
- Use bearer-token authentication for the mobile API. Prefer Laravel Sanctum after checking installed Laravel/package compatibility. Do not build a custom token system.
- Keep session authentication and `single.device` middleware for the existing web routes. Do not apply web-only middleware to API routes.
- Return a consistent envelope:

  ```json
  {
    "data": {},
    "meta": {}
  }
  ```

  For errors, use Laravel's standard JSON validation shape, with a stable application error code where a domain action fails.
- Use Laravel API Resources (or equivalent dedicated serializers) so models are never returned directly.
- Paginate collection endpoints. Maximum page size: 100. Support explicit filters and stable sort order.
- Use route model binding and return `404` for inaccessible or missing records where exposing existence would be unsafe.
- Apply rate limits to authentication and state-changing routes.
- Use idempotency keys for payment receipt and other retry-sensitive financial writes.
- Require `Idempotency-Key` for transfer creation and capital-account creation because both may post financial entries.
- Store monetary values as decimal strings in JSON, and serialize currency codes explicitly.
- Never expose password hashes, remember tokens, Telegram link codes, or unnecessary internal account/security fields.

## Target endpoint scope

### Authentication and profile

| Method | Path | Purpose |
|---|---|---|
| `POST` | `/auth/login` | Validate credentials and issue a bearer token |
| `POST` | `/auth/logout` | Revoke the current token |
| `GET` | `/me` | Return the authenticated user's safe profile and role |

### Transfers

| Method | Path | Purpose |
|---|---|---|
| `GET` | `/transfers` | Paginated transfer list with status, payment status, date, and reference filters |
| `POST` | `/transfers/calculate` | Return a quote using the existing calculator service |
| `POST` | `/transfers` | Create a transfer and optionally record an initial payment through the established workflow |
| `GET` | `/transfers/{transfer}` | Return transfer details, safe payment history, and permitted proof metadata |
| `PATCH` | `/transfers/{transfer}` | Update eligible pending transfer details using the existing edit rules |
| `POST` | `/transfers/{transfer}/payments` | Receive a customer installment |
| `POST` | `/transfers/{transfer}/execute` | Upload proof and execute a pending transfer |
| `POST` | `/transfers/{transfer}/cancel` | Cancel an eligible pending transfer |
| `POST` | `/transfers/{transfer}/force-cancel` | Admin-only reversal of a completed transfer; require password confirmation and audit notes |

### Dashboard and reference data

| Method | Path | Purpose |
|---|---|---|
| `GET` | `/dashboard/summary` | Authorized financial summary and transfer/payment counts |
| `GET` | `/exchange-rates` | List supported currency rates |
| `PUT` | `/exchange-rates/{currency}` | Update a rate under the existing permission rule |

### Capital and audit

| Method | Path | Purpose |
|---|---|---|
| `GET` | `/capital-accounts` | List accounts visible to the authenticated role |
| `POST` | `/capital-accounts` | Create an account with audit-safe opening balance handling |
| `PATCH` | `/capital-accounts/{account}` | Update permitted account metadata or active status |
| `POST` | `/capital-accounts/{account}/adjustments` | Record a manual balance adjustment with required notes |
| `POST` | `/capital-accounts/{account}/profit-withdrawals` | Withdraw from an eligible profit account |
| `POST` | `/capital-transfers/preview` | Calculate a capital transfer preview |
| `POST` | `/capital-transfers` | Execute a capital transfer through `CapitalTransferService` |
| `GET` | `/capital-transfers` | Paginated capital transfer history |
| `GET` | `/capital-transactions` | Admin-only paginated ledger with account/type/date filters |

### User administration

| Method | Path | Purpose |
|---|---|---|
| `GET` | `/users` | Admin-only paginated user list |
| `POST` | `/users` | Admin creates a user |
| `GET` | `/users/{user}` | Admin reads safe user details |
| `PATCH` | `/users/{user}` | Admin updates permitted profile, role, and active fields |
| `PUT` | `/users/{user}/password` | Admin sets a password without returning it |
| `DELETE` | `/users/{user}` | Admin deactivates or deletes according to existing integrity rules; never allow removing/deactivating self if the current UI prevents it |

## Permission model

Use the existing role/Gate behavior as the baseline and explicitly authorize every API handler. The API must not assume that visibility in the UI is sufficient authorization.

- Admin: all actions, subject to the existing force-cancel password confirmation and audit requirements.
- Coordinator: create/update transfers, receive payments, cancel pending transfers, view financial information and perform the capital operations currently allowed by the application.
- Executor: execute transfers, view permitted financial data, and update exchange rates where currently allowed.
- Transfer executor: view transfers and execute transfers; do not grant capital-account, ledger, or dashboard access where current gates deny it.
- User administration and capital ledger: admin only.

Before implementing, reconcile any mismatch between README permission tables and actual gates/Livewire authorization. Actual enforced behavior is authoritative; document any intentional change before making it.

## Implementation phases and acceptance criteria

### Phase 1: API foundation and auth

1. Confirm compatible token-auth package availability and install Sanctum only if not already present or available in the lockfile.
2. Register `routes/api.php` and create the `/api/v1` route group.
3. Add token login, current-user, and logout endpoints with throttling.
4. Add safe response resources, a consistent error convention, and API-specific middleware configuration.
5. Keep web login/logout/session behavior unchanged.

**Tests after Phase 1:** API feature tests for unauthenticated JSON `401`, valid login and `/me`, invalid credentials, inactive user denial, token revocation on logout, rate limiting, and unchanged web login/session behavior.

**Complete when:** Phase 1 tests pass; unauthenticated requests receive JSON `401`; valid tokens can call `/me`; logout revokes the current token; inactive users cannot obtain or use tokens; web authentication still uses its existing flow.

### Phase 2: Read-only transfer and reference endpoints

1. Add transfer list, detail, and calculation endpoints.
2. Add exchange-rate listing and dashboard summary endpoints.
3. Add pagination, filters, role authorization, and API Resources.

**Tests after Phase 2:** feature tests for list/detail/calculation and dashboard/rate reads, pagination and supported filters, safe response fields, missing-record behavior, and role-based access for each role.

**Complete when:** Phase 2 tests pass; responses contain only approved fields, pagination/filter behavior is stable, and users receive the same visibility constraints as the current application.

### Phase 3: Transfer creation and maintenance

1. Extract transfer creation and update logic from Livewire components into reusable application services where needed.
2. Add validated create and update requests, preserving the existing calculation and pending-state rules.
3. Record the acting creator ID explicitly and dispatch existing events once per successful creation. The model has a `pricer` relationship but the schema has no `priced_by` column; do not add or populate it until its business meaning is defined.

**Tests after Phase 3:** feature tests for valid and invalid creation, both calculation modes, optional initial payment, role denial, pending-only update rules, audit actor fields, event dispatch, and parity with the shared web workflow.

**Complete when:** Phase 3 tests pass; API-created and web-created transfers follow the same domain rules and produce consistent persisted records/events.

### Phase 4: Payment and lifecycle actions

1. Refactor actor identity in payment, execution, cancellation, and force-cancellation services to be explicit inputs.
2. Add payment receipt with idempotency protection and concurrency-safe balance/transfer locking.
3. Add execution proof upload, cancellation, and admin force-cancellation endpoints.
4. Preserve existing transaction boundaries, ledger entries, notifications, proof storage, and password-confirmation behavior.

**Tests after Phase 4:** feature tests for payment success and validation, repeated idempotency keys, concurrent/duplicate payment protection where supported by the test database, proof file validation/storage, execution success and insufficient funds, pending cancellation/refund, force-cancel password and role checks, invalid transitions, ledger balance/transaction integrity, and event dispatch.

**Complete when:** Phase 4 tests pass; duplicate/retried requests cannot double-post financial entries; invalid state transitions fail with stable JSON errors; ledger balances and audit fields remain consistent.

### Phase 5: Capital operations and user administration

1. Extract remaining Livewire-only capital account/rate/user mutations into reusable services as appropriate.
2. Add account listing/management, adjustments, withdrawals, transfer preview/execution/history, and restricted ledger endpoints.
3. Add admin-only user management with safe serialization and self-protection rules.

**Tests after Phase 5:** feature tests for every capital operation's authorization, validation, successful balance/ledger effects and insufficient-balance failure; exchange-rate updates; user CRUD/password behavior, safe serialization, and self-protection rules.

**Complete when:** Phase 5 tests pass; every write uses the existing financial services or a shared extracted service, all role checks are enforced server-side, and every balance change produces its required ledger transaction.

### Phase 6: API documentation and full verification

1. Add OpenAPI documentation for routes, authentication, payloads, response schemas, filters, and error codes.
2. Review phase-level coverage for authentication, authorization by role, validation, state transitions, idempotency, proof upload, and ledger consistency; fill any remaining gaps.
3. Run the full test suite and formatter, resolve failures introduced by this work, and report any pre-existing failures separately.

**Tests after Phase 6:** run the full suite, confirm all API feature tests are included, and validate the OpenAPI document against the implemented routes and payloads.

**Complete when:** Phase 6 checks pass; the API contract is reviewable and automated coverage verifies key success and failure paths for every endpoint group.

## Non-goals for this implementation

- Do not replace or redesign the Livewire UI.
- Do not expose Telegram webhook/link management through this API.
- Do not add dynamic corridors, branches, or currencies; these are separate product changes.
- Do not add customer-facing transfer access or third-party client credentials in this first version.
- Do not alter existing business rules unless an implementation blocker or correctness issue is documented and resolved explicitly.

## Execution rule

When implementation begins, complete one phase at a time, preserve unrelated working-tree edits, and report phase completion before moving to the next phase. If a security or accounting issue blocks a planned endpoint, stop that endpoint, document the issue and proposed correction, and keep the rest of the API scope aligned with this plan.
