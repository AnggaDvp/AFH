# Debug Session: regions-origin-mismatch
- **Status**: [OPEN]
- **Issue**: Customer Gate displays "Data wilayah gagal dimuat." while the Jabodetabek dataset and backend tests previously passed.
- **Debug Server**: http://127.0.0.1:7777/event
- **Log File**: .dbg/trae-debug-log-regions-origin-mismatch.ndjson

## Reproduction Steps
1. Inspect the browser page origin and current network request for `GET /api/regions`.
2. Inspect Laravel listeners and registered routes.
3. Request `/api/regions` directly from the Laravel server(s) on ports 8000 and 8093.
4. Open Customer Gate and inspect the dependent dropdown requests.

## Hypotheses & Verification
| ID | Hypothesis | Likelihood | Effort | Expected Signal | Evidence |
|----|------------|------------|--------|-----------------|----------|
| A | Browser and Laravel use different origins/instances. | High | Low | Page is on `localhost:8000`, while only `127.0.0.1:8093` serves the API route/data. | Rejected as the cause: PHP/Laravel listens on `127.0.0.1:8000`; port 8093 refuses connections. |
| B | The route-serving instance cannot connect to its configured database. | Medium | Low | API request returns 500 and Laravel reports a database connection error. | Confirmed: browser and direct requests return 500 with SQLSTATE 2002; MySQL on 127.0.0.1:3306 refuses connections. |
| C | The API responds but has an unexpected status or JSON shape. | Medium | Low | Direct request succeeds, but response is not a JSON array accepted by the component. | The shape is not the issue: with Accept: application/json the server returns a JSON 500 containing the database connection error. |
| D | Browser request is blocked by CORS or connection failure. | Medium | Low | Network panel reports CORS rejection or connection refused. | CORS rejected: page and API are same-origin. Browser-side fetch received HTTP 500; response included Access-Control-Allow-Origin: *. |

## Log Evidence
1. Browser page origin and request URL: `http://localhost:8000`; `GET /api/regions` (browser event recorded in `.dbg/trae-debug-log-regions-origin-mismatch.ndjson`, line 1) returned 500 with SQLSTATE 2002.
2. Direct `GET http://127.0.0.1:8000/api/regions` returned HTTP 500. Without Accept: application/json Laravel returned an HTML exception page; with that header it returned JSON.
3. `GET http://127.0.0.1:8093/api/regions` failed to connect.
4. PHP process on port 8000 is `php.exe` serving Laravel's `server.php`. The route exists under API middleware (`api`); no `auth:sanctum` middleware is attached.
5. `.env` targets MySQL at `127.0.0.1:3306`, database `laravel`. `mysqladmin ping` returned connection error 10061. An XAMPP `mysqld.exe` process exists but owns no listening socket; its error log has not been updated since 2026-09-19.
6. The existing MySQL `laravel` data directory contains only `migrations` and `framework_health_checks` tables, not the regions/districts/villages tables. Prior successful migration/seed verification was on in-memory SQLite and did not populate this MySQL database.
7. Frontend fetches relative `/api/regions`, so it uses the page origin correctly. No CORS issue or frontend URL hardcode was found.
8. With user authorization, the stale MariaDB process was stopped and XAMPP's supported startup command was attempted. Startup failed because the sandbox denied MariaDB access to create temporary files under `C:\xampp\tmp`; port 3306 remains unavailable. No migration or seeder was run.
9. In-memory SQLite backend tests: 7 passed. `npm run typecheck` and `npm run build` passed. These do not prove the configured MySQL endpoint is healthy.

## Verification Conclusion
Root cause is the unavailable MySQL listener and the absence of persistent region schema/data in the configured MySQL database. No application source code was changed. Startup was attempted with authorization but blocked by sandbox file permissions. The existing migration/seeder and browser post-fix verification remain pending until MariaDB can start outside the sandbox.
