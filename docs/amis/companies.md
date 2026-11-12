# Public Companies API

API documentation for rent-land companies under `/api/v1/public/companies`. All endpoints require `X-API-Key` authentication.

This API is **read-only**. It lists companies that are linked on rent-land contracts (`as_contact_lands.partner` JSON `{company_id, shareholding_pct}`), grouped with nested `rent_lands[]`. Companies with no rent-land links are not returned.

## Endpoints Overview

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/v1/public/companies` | Paginated companies grouped with rent-land contracts |
| GET | `/api/v1/public/companies/:id` | Single company with nested rent-land contracts |

**Related rent-land endpoints:**

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/v1/public/income/rent-land` | Paginated rent-land contracts |
| GET | `/api/v1/public/income/rent-land/:id` | Single rent-land contract |
| GET | `/api/v1/public/local/provinces` | Province lookup |
| GET | `/api/v1/public/local/districts/:provinceId` | District lookup |

See [income-rent-land.md](./income-rent-land.md).

---

## Authentication

| Header | Type | Required | Description |
|--------|------|----------|-------------|
| `X-API-Key` | string | Yes | Valid API key issued by AMIS admin |

**Authentication validation:**
- `X-API-Key` header is required on every request
- API key must exist, be active, and not be expired
- Domain restriction may apply depending on key configuration

**Error format (all endpoints):**
```json
{
  "message": "Human-readable message",
  "code": "ERROR_CODE",
  "detail": "Additional details"
}
```

**Common auth errors:**

| HTTP | Code | Message |
|------|------|---------|
| 401 | `API_KEY_INVALID` | API key is required / Invalid API key |
| 403 | `API_KEY_INACTIVE` | API key is inactive |
| 403 | `API_KEY_EXPIRED` | API key has expired |

---

## 1. List Companies

- **Method:** GET
- **Path:** `/api/v1/public/companies`
- **Pagination:** Yes | **Filters:** Yes

Returns distinct companies that appear on at least one rent-land contract. Each company includes `rent_land_count` and nested `rent_lands[]`.

### Query Parameters

| Parameter | Type | Default | Validation | Description |
|-----------|------|---------|------------|-------------|
| `page` | integer | 1 | min: 1 | Page number |
| `page_size` | integer | 20 | min: 1, max: 100 | Items per page (`page_size` above 100 is clamped to 100) |
| `search` | string | — | — | Partial match on `name_lao`, `name_eng`, `tax_id`, or `phone` |
| `is_active` | boolean | — | Go `ParseBool` (`true`/`false`, `1`/`0`, `t`/`f`) | Filter by company active flag |

### Filters

| Parameter | Match Type | Column |
|-----------|------------|--------|
| `search` | partial OR | `cc.name_lao`, `cc.name_eng`, `cc.tax_id`, `cc.phone` |
| `is_active` | exact | `cc.is_active` |

### Success (200 OK)

```json
{
  "message": "Companies retrieved successfully",
  "companies": [
    {
      "id": 12,
      "name_lao": "ບໍລິສັດ ABC",
      "name_eng": "ABC Company",
      "company_type": "company",
      "tax_id": "123456789",
      "phone": "02055551234",
      "province_id": 1,
      "province_name": "ນະຄອນຫຼວງວຽງຈັນ",
      "district_id": 104,
      "district_name": "ໄຊເສດຖາ",
      "village": "ບ້ານນາ",
      "is_active": true,
      "rent_land_count": 2,
      "rent_lands": [
        {
          "contact_id": 87,
          "contract_type": "concession",
          "contact_name": "ໂຄງການສຳປະທານ",
          "contact_number": "5871/MTC",
          "contact_date_start": "2024-01-01",
          "contact_date_end": "2029-01-01",
          "status": "nomal",
          "shareholding_pct": 60
        },
        {
          "contact_id": 42,
          "contract_type": "rental",
          "contact_name": "ສັນຍາເຊົ່າ",
          "contact_number": "1200/MTC",
          "contact_date_start": "2023-06-01",
          "contact_date_end": "2026-06-01",
          "status": "nomal",
          "shareholding_pct": 100
        }
      ]
    }
  ],
  "total": 35,
  "page": 1,
  "page_size": 20,
  "total_pages": 2
}
```

**Pagination:** `total_pages = ceil(total / page_size)`. Empty results return `companies: []`, `total: 0`. Sort is `cc.id DESC`. Nested `rent_lands[]` is ordered by `contact_id DESC`.

### Endpoint-Specific Errors

| HTTP | Code | When |
|------|------|------|
| 400 | `BAD_REQUEST` | Invalid `is_active` |
| 500 | `INTERNAL_ERROR` | Failed to fetch companies |

### Usage

```bash
# Basic list
curl -X GET "http://localhost:5525/api/v1/public/companies?page=1&page_size=20" \
  -H "X-API-Key: your-api-key-here"

# Active companies matching a name or tax ID
curl -X GET "http://localhost:5525/api/v1/public/companies?is_active=true&search=ABC" \
  -H "X-API-Key: your-api-key-here"
```

### Business Logic

1. Validate API key
2. Parse pagination (`page` default 1, `page_size` default 20, max 100)
3. Expand `as_contact_lands.partner` JSON with `JSON_TABLE` (`$[*].company_id`, `$[*].shareholding_pct`)
4. Join `as_contract_companies` on `company_id`; skip invalid / legacy plus-separated partner text
5. Count distinct companies, then fetch the page grouped by `cc.id`
6. Attach nested `rent_lands[]` for the companies on that page (duplicate contract IDs per company are dropped)

---

## 2. Get Company By ID

- **Method:** GET
- **Path:** `/api/v1/public/companies/:id`
- **Pagination:** No | **Filters:** No

Returns one company that is linked to at least one rent-land contract. Same object shape as list items.

### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | Company ID (`as_contract_companies.id`, must be > 0) |

### Success (200 OK)

```json
{
  "message": "Company retrieved successfully",
  "company": {
    "id": 12,
    "name_lao": "ບໍລິສັດ ABC",
    "name_eng": "ABC Company",
    "company_type": "company",
    "tax_id": "123456789",
    "phone": "02055551234",
    "province_id": 1,
    "province_name": "ນະຄອນຫຼວງວຽງຈັນ",
    "district_id": 104,
    "district_name": "ໄຊເສດຖາ",
    "village": "ບ້ານນາ",
    "is_active": true,
    "rent_land_count": 1,
    "rent_lands": [
      {
        "contact_id": 87,
        "contract_type": "concession",
        "contact_name": "ໂຄງການສຳປະທານ",
        "contact_number": "5871/MTC",
        "contact_date_start": "2024-01-01",
        "contact_date_end": "2029-01-01",
        "status": "nomal",
        "shareholding_pct": 60
      }
    ]
  }
}
```

### Endpoint-Specific Errors

| HTTP | Code | When |
|------|------|------|
| 400 | `BAD_REQUEST` | Invalid or non-positive `id` |
| 404 | `NOT_FOUND` | Company does not exist, or exists but is not linked to any rent-land contract |
| 500 | `INTERNAL_ERROR` | Failed to retrieve company |

### Usage

```bash
curl -X GET "http://localhost:5525/api/v1/public/companies/12" \
  -H "X-API-Key: your-api-key-here"
```

### Business Logic

1. Validate API key and path `id` (`> 0`)
2. Group rent-land partner JSON onto `as_contract_companies` where `cc.id` matches
3. Return 404 when the company has no rent-land links
4. Attach nested `rent_lands[]` ordered by `contact_id DESC`

---

## Data Model

### Company Object

| Field | Type | Nullable | Description |
|-------|------|----------|-------------|
| `id` | integer | No | Company primary key (`as_contract_companies.id`) |
| `name_lao` | string | No | Company name (Lao) |
| `name_eng` | string | Yes | Company name (English) |
| `company_type` | string | No | `company`, `shop`, `individual`, or `state_enterprise` |
| `tax_id` | string | Yes | Tax identification number |
| `phone` | string | Yes | Phone number |
| `province_id` | integer | Yes | Registered province ID |
| `province_name` | string | Yes | Province name from `as_provinces` |
| `district_id` | integer | Yes | Registered district ID |
| `district_name` | string | Yes | District name from `as_dristrics` |
| `village` | string | Yes | Registered village |
| `is_active` | boolean | No | Whether the company record is active |
| `rent_land_count` | integer | No | Distinct rent-land contracts linked to this company |
| `rent_lands` | array | No | Nested rent-land summaries (empty array when none on the page after grouping) |

### CompanyRentLand Object (`rent_lands[]`)

| Field | Type | Nullable | Description |
|-------|------|----------|-------------|
| `contact_id` | integer | No | Rent-land contract ID |
| `contract_type` | string | No | `concession` or `rental` |
| `contact_name` | string | No | Contract name |
| `contact_number` | string | Yes | Contract number |
| `contact_date_start` | string | Yes | Start date `YYYY-MM-DD` |
| `contact_date_end` | string | Yes | End date `YYYY-MM-DD` |
| `status` | string | No | `nomal`, `cancel`, or `end` (legacy spelling `nomal`) |
| `shareholding_pct` | number | Yes | Shareholding percent from the partner JSON link (`0`–`100`) |

### Enums

| Field | Values |
|-------|--------|
| `company_type` | `company`, `shop`, `individual`, `state_enterprise` |
| `contract_type` | `concession`, `rental` |
| `status` | `nomal`, `cancel`, `end` |

---

## Notes

- Partner data on rent-land is stored as JSON `[{ "company_id": 12, "shareholding_pct": 60 }]`. Legacy plus-separated partner text is ignored.
- A company can appear on multiple contracts; list pagination is by **company**, not by contract.
- Full contract payload (items, organization, payment schedule) is on `GET /income/rent-land/:id`, not on this grouping API.

---

## Testing Checklist

| # | Scenario | Expected |
|---|----------|----------|
| 1 | Valid API key → list | 200 with paginated `companies` and nested `rent_lands` |
| 2 | Valid API key → detail (company linked to rent-land) | 200 with `company` |
| 3 | Company exists but has no rent-land links | 404 |
| 4 | Non-existent company id | 404 |
| 5 | Invalid or non-positive `id` | 400 `BAD_REQUEST` |
| 6 | Invalid `is_active` | 400 `BAD_REQUEST` |
| 7 | No matching list filters | 200 with `companies: []`, `total: 0` |
| 8 | Missing API key | 401 |

**Tools:** curl, Postman, Insomnia, HTTPie

**Insomnia collection:** Import `public-api-collection.insomnia.json` (not included)
