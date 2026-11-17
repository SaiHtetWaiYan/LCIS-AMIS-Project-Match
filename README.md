# LCIS–AMIS Project Match

Links land concession projects in **LCIS** to contracts in **AMIS**. The app copies both systems
every night, suggests likely pairs by company, activity, location and area, and lets one side
propose a match and the other side confirm it. Confirmed matches are published through a
read-only API.

## Setup

```bash
composer install
npm install && npm run build
cp .env.example .env && php artisan key:generate
php artisan migrate
php artisan user:create-admin
```

Set the source credentials in `.env`:

| Key | Value |
|---|---|
| `LCIS_API_BASE_URL` | `http://10.10.0.243/api/v1` |
| `LCIS_API_TOKEN` | Bearer token of an LCIS API client |
| `AMIS_API_BASE_URL` | `https://api.amis.gov.la/api/v1/public` |
| `AMIS_API_KEY` | AMIS public API key |

## Syncing

Both sources are copied in full every night by the scheduler (LCIS at 01:00 UTC, AMIS at 01:30 UTC),
running on the queue worker. An Admin can also start a sync from the admin sync page, or run one
in the terminal:

```bash
php artisan sync:run lcis
php artisan sync:run amis
```

`php artisan matching:reindex` and `php artisan matching:score` rebuild the suggestion index and
the best scores shown on the lists; syncs already do both.

## Matches API

`GET /api/v1/matches` returns the **confirmed** LCIS ↔ AMIS pairs. Proposed, rejected, withdrawn
and unmatched pairs are not included: when a pair is unmatched it simply leaves the list. Fetch
the full list each time and replace your copy, rather than adding to it.

### Access

Each calling system gets its own bearer token. Only a SHA-256 hash of the token is stored, so it
is shown once, when it is created:

```bash
php artisan api-client:create AMIS            # prints the client id and its token
php artisan api-client:revoke 1               # the token stops working
php artisan api-client:revoke 1 --restore     # the token works again
```

Send the token in the `Authorization` header. Requests are limited to 120 per minute per token.

```bash
curl -H "Authorization: Bearer <token>" "http://10.10.0.243:8080/api/v1/matches?per_page=100&page=1"
```

`GET /api/v1/ping` needs no token and returns `{"status": "ok"}`.

### Query parameters

| Parameter | Description |
|---|---|
| `page` | Page number, from 1 |
| `per_page` | Rows per page, 1–500 (default 100) |
| `lcis_project_id` | Only the match of this LCIS project id |
| `amis_contract_id` | Only the match of this AMIS contract id (`contact_id` in AMIS) |

### Response

Newest match first.

```json
{
  "data": [
    {
      "id": 5,
      "lcis": {
        "project_id": 1903,
        "project_code": "03706.01",
        "activity_name": "ເຂື່ອນໄຟຟ້ານໍ້າໂມ້ 2",
        "company_name": "ບໍລິສັດ ໄຟຟ້ານໍ້າໂມ້ 2 ຈຳກັດ"
      },
      "amis": {
        "contract_id": 167,
        "contract_number": "0",
        "contract_type": "concession",
        "contract_name": "ໂຄງການເຂື່ອນໄຟຟ້ານ້ຳໂມ້ 2"
      },
      "matched_at": "2026-11-16 06:25:17",
      "needs_review": true
    }
  ],
  "meta": { "total": 1, "per_page": 100, "current_page": 1, "last_page": 1 }
}
```

- Link records by `lcis.project_id` and `amis.contract_id`. The AMIS `contract_number` is not
  reliable: some contracts have `"0"` or a date there.
- `needs_review` is `true` when matching fields changed in LCIS or AMIS after the pair was
  confirmed. The match stays valid until an Admin reviews or unmatches it.
- Times are UTC, formatted `YYYY-MM-DD HH:MM:SS`.

### Errors

Errors use this envelope (a 429 returns only `{ "message": "Too Many Attempts." }`):

```json
{ "error": { "code": "UNAUTHORIZED", "message": "Invalid or revoked token.", "status": 401 } }
```

| Status | Code | When |
|---|---|---|
| 401 | `UNAUTHORIZED` | Missing, unknown or revoked token |
| 422 | `VALIDATION_FAILED` | An invalid parameter; `error.fields` lists each problem |
| 429 | — | More than 120 requests in a minute |

## Tests

```bash
php artisan test --compact
```
