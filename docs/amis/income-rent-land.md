# Public Rent Land API

API documentation for rent land contract endpoints under `/api/v1/public/income/rent-land`. All endpoints require `X-API-Key` authentication.

## Endpoints Overview

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/v1/public/income/rent-land/quantity` | Total contract count |
| GET | `/api/v1/public/income/rent-land` | Paginated list with filters |
| GET | `/api/v1/public/income/rent-land/:id` | Single contract detail |
| GET | `/api/v1/public/income/rent-land/invoices` | Paginated rent-land invoice list (ໃບຄິດໄລ່) |
| GET | `/api/v1/public/income/rent-land/invoices/:id` | Single rent-land invoice detail |
| GET | `/api/v1/public/income/rent-land/type-contacts` | Distinct `type_contact` values for filters |
| GET | `/api/v1/public/income/rent-land/contract-types` | Distinct `contract_type` values with Lao labels for filters |

**Related lookup endpoints (not under rent-land):**

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/v1/public/local/provinces` | Province lookup |
| GET | `/api/v1/public/local/districts/:provinceId` | District lookup |
| GET | `/api/v1/public/companies` | Companies grouped with nested rent-land contracts |
| GET | `/api/v1/public/companies/:id` | Single company with nested rent-land contracts |

See [companies.md](./companies.md).

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

## 1. Fetch Rent Land Quantity

- **Method:** GET
- **Path:** `/api/v1/public/income/rent-land/quantity`
- **Pagination:** No | **Filters:** No

No query parameters or request body.

### Success (200 OK)

```json
{
  "message": "Rent land quantity fetched successfully",
  "data": {
    "quantity": 856
  }
}
```

| Field | Type | Description |
|-------|------|-------------|
| `data.quantity` | integer | Total row count from `as_contact_lands` |

### Business Logic

1. Validate API key
2. Execute `COUNT(*)` on `as_contact_lands`
3. Return `data.quantity` (`0` is valid when no contracts exist)

### Usage

```bash
curl -X GET "http://localhost:5525/api/v1/public/income/rent-land/quantity" \
  -H "X-API-Key: your-api-key-here"
```

---

## 2. List Rent Land Contracts

- **Method:** GET
- **Path:** `/api/v1/public/income/rent-land`
- **Pagination:** Yes | **Filters:** Yes

### Query Parameters

| Parameter | Type | Default | Validation | Description |
|-----------|------|---------|------------|-------------|
| `page` | integer | 1 | min: 1 | Page number |
| `page_size` | integer | 20 | min: 1 | Items per page |
| `type` | string | — | `concession` or `rental` | Contract type filter (alias) |
| `contract_type` | string | — | `concession` or `rental` | Contract type filter |
| `contact_number` | string | — | — | Partial match on contract number |
| `contact_date_start` | string | — | `YYYY-MM-DD` | Exact match on signing date |
| `type_contact` | string | — | — | Partial match on company type |
| `partner` | string | — | — | Partial match on partner name |
| `from_province_id` | integer | — | min: 1 | Budget province ID |
| `start_date` | string | — | `YYYY-MM-DD` | Created from (inclusive) |
| `end_date` | string | — | `YYYY-MM-DD` | Created to (inclusive) |
| `search` | string | — | — | Search `contact_number` or `contact_name` |

### Filters

| Parameter | Match Type | Column |
|-----------|------------|--------|
| `type` | exact | `contract_type` |
| `contract_type` | exact | `contract_type` |
| `contact_number` | partial (LIKE) | `contact_number` |
| `contact_date_start` | exact | `contact_date_start` |
| `type_contact` | partial (LIKE) | `type_contact` |
| `partner` | partial (LIKE) | `partner` |
| `from_province_id` | exact | `from_province_id` |
| `start_date` | gte | `created_at` |
| `end_date` | lte | `created_at` |
| `search` | partial OR | `contact_number`, `contact_name` |

### Success (200 OK)

```json
{
  "message": "Rent land contracts retrieved successfully",
  "rent_lands": [
    {
      "contact_id": 1,
      "contract_type": "concession",
      "business_type_id": 1,
      "business_type_name": "ກະສິກຳ",
      "contact_number": "5871/MTC",
      "contact_name": "ໂຄງການສຳປະທານ",
      "partner": ["ບໍລິສັດ ABC"],
      "status": "nomal",
      "items": []
    }
  ],
  "total": 100,
  "page": 1,
  "page_size": 20,
  "total_pages": 5
}
```

Each `rent_lands[]` entry includes all contract columns plus nested `items[]`. See [Data Model](#data-model) for full field reference.

**Pagination:** `total_pages = ceil(total / page_size)`. Empty results return `rent_lands: []`, `total: 0`.

### Endpoint-Specific Errors

| HTTP | Code | When |
|------|------|------|
| 400 | `INVALID_INPUT` | Invalid `type`, `contract_type`, or `from_province_id` |
| 500 | `INTERNAL_ERROR` | Failed to fetch rent lands |

### Usage

```bash
# Basic list
curl -X GET "http://localhost:5525/api/v1/public/income/rent-land?page=1&page_size=20" \
  -H "X-API-Key: your-api-key-here"

# With filters (contract_type or type)
curl -X GET "http://localhost:5525/api/v1/public/income/rent-land?contract_type=concession&search=5871" \
  -H "X-API-Key: your-api-key-here"
```

### Business Logic

1. Validate API key
2. Parse pagination (`page` default 1, `page_size` default 20)
3. Apply filters on `as_contact_lands`
4. Count matching rows, fetch paginated results (`contact_id DESC`, `updated_at DESC`)
5. Load nested `items[]` per contract from `as_contact_land_item`

---

## 3. Get Rent Land By ID

- **Method:** GET
- **Path:** `/api/v1/public/income/rent-land/:id`
- **Pagination:** No | **Filters:** No

### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | Contract ID (`contact_id`, must be > 0) |

### Success (200 OK)

```json
{
  "message": "Rent land contract retrieved successfully",
  "rent_land": {
    "contact_id": 1,
    "contract_type": "concession",
    "business_type_id": 1,
    "business_type_name": "ກະສິກຳ",
    "contact_number": "5871/MTC",
    "contact_name": "ໂຄງການສຳປະທານ",
    "contact_date_start": "2024-01-01",
    "contact_date_end": "2029-01-01",
    "payment_type_id": 83,
    "payment_type_name": "ລາຍຮັບສຳປະທານ",
    "partner": ["ບໍລິສັດ ABC"],
    "lessor": ["ກະຊວງ..."],
    "type_contact": ["0"],
    "rent_year": 5,
    "year_start_payment": "2024",
    "set_payment_date": "2024-01-15",
    "before_payment": "0",
    "contect_price": 4402.0,
    "payment_frequency": "yearly",
    "payment_amount": 880.4,
    "late_fee_type": "percentage",
    "late_fee_value": 15,
    "rent_to_year": [
      {
        "item_add_price_rent_to_year": "5",
        "item_price_rent_to_year_persen": 10
      }
    ],
    "from_province_id": 1,
    "from_province_name": "ນະຄອນຫຼວງວຽງຈັນ",
    "partner_address": {
      "province_id": 1,
      "province_name": "ນະຄອນຫຼວງວຽງຈັນ",
      "district_id": 104,
      "district_name": "ໄຊເສດຖາ",
      "village": "ບ້ານນາ"
    },
    "organization": {
      "level": "CENTER",
      "ministry_id": 1,
      "ministry_name": "ກະຊວງ..."
    },
    "rental_format": null,
    "status": "nomal",
    "file": "doc.pdf",
    "file_url": "https://...",
    "file_s3_uploaded": true,
    "created_by": {
      "id": 69,
      "firstname": "Admin",
      "lastname": "User"
    },
    "created_at": "2024-01-26T08:34:38Z",
    "updated_at": "2024-01-26T08:34:38Z",
    "items": [
      {
        "id": 1,
        "contact_land_id": 1,
        "rent_detell": "ກະສິກຳ",
        "province_id": 1,
        "province_name": "ນະຄອນຫຼວງວຽງຈັນ",
        "dristric_id": "104",
        "district_name": "ໄຊເສດຖາ",
        "village": "ບ້ານນາ",
        "detail_addess": "ເຂດ A",
        "area": 500,
        "area_land": 400,
        "area_build": 100,
        "unit": "ເຮັກຕາ",
        "rent_m_price": 100,
        "total_project": 5000000,
        "currency": "ກີບ",
        "latitude": "17.975706",
        "longitude": "102.633104"
      }
    ]
  }
}
```

### Endpoint-Specific Errors

| HTTP | Code | When |
|------|------|------|
| 400 | `INVALID_INPUT` | Invalid or non-positive `id` |
| 404 | `NOT_FOUND` | Contract does not exist |
| 500 | `INTERNAL_ERROR` | Failed to retrieve contract |

### Usage

```bash
curl -X GET "http://localhost:5525/api/v1/public/income/rent-land/1" \
  -H "X-API-Key: your-api-key-here"
```

### Business Logic

1. Validate API key and path `id`
2. Select all explicit columns from `as_contact_lands` with JOINs (no `SELECT *`)
3. Load `items[]` ordered by `id ASC`
4. Split `partner`/`lessor`/`type_contact` on `+`; parse `rent_to_year` JSON

---

## 4. Fetch Type Contacts

- **Method:** GET
- **Path:** `/api/v1/public/income/rent-land/type-contacts`
- **Pagination:** No | **Filters:** No

No query parameters. Returns distinct `type_contact` values from `as_contact_lands`, split on `+`, deduplicated, trimmed, and sorted.

### Success (200 OK)

```json
{
  "message": "Type contacts fetched successfully",
  "type_contacts": ["0", "1", "2"]
}
```

Use values to populate a filter dropdown for list query `?type_contact=` (partial match).

### Usage

```bash
curl -X GET "http://localhost:5525/api/v1/public/income/rent-land/type-contacts" \
  -H "X-API-Key: your-api-key-here"

# Apply filter on list
curl -X GET "http://localhost:5525/api/v1/public/income/rent-land?type_contact=0&page=1" \
  -H "X-API-Key: your-api-key-here"
```

---

## 5. Fetch Contract Types

- **Method:** GET
- **Path:** `/api/v1/public/income/rent-land/contract-types`
- **Pagination:** No | **Filters:** No

No query parameters. Returns distinct `contract_type` values from `as_contact_lands`, each with a Lao label for filter dropdown UI.

### Success (200 OK)

```json
{
  "message": "Contract types fetched successfully",
  "contract_types": [
    {
      "code": "concession",
      "label": "ສຳປະທານ"
    },
    {
      "code": "rental",
      "label": "ສັນຍາເຊົ່າ"
    }
  ]
}
```

| Field | Type | Description |
|-------|------|-------------|
| `contract_types[].code` | string | Filter value: `concession` or `rental` |
| `contract_types[].label` | string | Lao display label |

Use `code` values to populate a filter dropdown for list query `?contract_type=` or `?type=` (both parameters are equivalent).

### Usage

```bash
curl -X GET "http://localhost:5525/api/v1/public/income/rent-land/contract-types" \
  -H "X-API-Key: your-api-key-here"

# Apply filter on list
curl -X GET "http://localhost:5525/api/v1/public/income/rent-land?contract_type=concession&page=1" \
  -H "X-API-Key: your-api-key-here"
```

---

## 6. List Rent Land Invoices (ໃບຄິດໄລ່)

- **Method:** GET
- **Path:** `/api/v1/public/income/rent-land/invoices`
- **Pagination:** Yes | **Filters:** Yes

Returns invoices from `as_income_invoice` where `contact_type = 'rent_land'` and `invoice_type = 'invoice'`. Invoice creation remains admin-only; this endpoint is read-only. Neither filter column is exposed in the JSON response.

### Query Parameters

| Parameter | Type | Default | Validation | Description |
|-----------|------|---------|------------|-------------|
| `page` | integer | 1 | min: 1 | Page number |
| `page_size` | integer | 20 | min: 1 | Items per page |
| `status` | string | — | `normal`, `payment`, or `cancel` | Invoice status filter |
| `contact_id` | integer | — | min: 1 | Parent contract ID (`contact_id`) |
| `invoice_number` | string | — | — | Partial match on invoice number |
| `partner` | string | — | — | Partial match on partner name |
| `start_date` | string | — | `YYYY-MM-DD` | Due date from (inclusive, `date_payment`) |
| `end_date` | string | — | `YYYY-MM-DD` | Due date to (inclusive, `date_payment`) |

### Filters

| Parameter | Match Type | Column |
|-----------|------------|--------|
| `status` | exact | `status` |
| `contact_id` | exact | `contact_id` |
| `invoice_number` | partial (LIKE) | `invoice_number` |
| `partner` | partial (LIKE) | `partner` |
| `start_date` | gte | `date_payment` |
| `end_date` | lte | `date_payment` |

### Success (200 OK)

```json
{
  "message": "Invoices retrieved successfully",
  "invoices": [
    {
      "id": 42,
      "invoice_number": "0001 ",
      "contact_id": 1,
      "partner": "ບໍລິສັດ ABC",
      "date_payment": "2026-01-15",
      "total": 400.00,
      "service_total": 50.00,
      "form_print_price": 25.00,
      "status": "normal",
      "contact_type": "rent_land",
      "currency": "ກີບ",
      "province_id": 1,
      "province_name": "ນະຄອນຫຼວງວຽງຈັນ",
      "days_overdue": 10,
      "calculated_late_fee": 60.00,
      "subtotal": 400.00,
      "grand_total": 535.00
    }
  ],
  "total": 100,
  "page": 1,
  "page_size": 20,
  "total_pages": 5
}
```

### Field Notes

| Field | Description |
|-------|-------------|
| `total` | Rent subtotal only (sum of line item subtotals) |
| `service_total` | Service fee (ຄ່າບໍລິການ) |
| `form_print_price` | Form print fee (ຄ່າແບບພິມ) |
| `calculated_late_fee` | Computed dynamically when overdue; not stored in DB |
| `grand_total` | `total + service_total + form_print_price + calculated_late_fee` |
| `province_name` | Budget province name from `as_provinces` join |

### Endpoint-Specific Errors

| HTTP | Code | When |
|------|------|------|
| 400 | `INVALID_INPUT` | Invalid `status` or `contact_id` |
| 500 | `INTERNAL_ERROR` | Failed to fetch invoices |

### Usage

```bash
curl -X GET "http://localhost:5525/api/v1/public/income/rent-land/invoices?page=1&page_size=20" \
  -H "X-API-Key: your-api-key-here"

curl -X GET "http://localhost:5525/api/v1/public/income/rent-land/invoices?contact_id=1&status=normal" \
  -H "X-API-Key: your-api-key-here"
```

### Business Logic

1. Validate API key
2. Parse pagination (`page` default 1, `page_size` default 20)
3. Query `as_income_invoice` with fixed `contact_type = 'rent_land'` and `invoice_type = 'invoice'` (neither exposed in response)
4. Join `as_provinces` on `province_id` for `province_name`
5. Compute late fee from parent contract when status is `normal` and overdue

---

## 7. Get Rent Land Invoice By ID

- **Method:** GET
- **Path:** `/api/v1/public/income/rent-land/invoices/:id`
- **Pagination:** No | **Filters:** No

### Path Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | Invoice ID (must be > 0) |

### Success (200 OK)

```json
{
  "message": "Invoice retrieved successfully",
  "invoice": {
    "id": 42,
    "invoice_number": "0001 ",
    "contact_id": 1,
    "partner": "ບໍລິສັດ ABC",
    "date_payment": "2026-01-15",
    "total": 400.00,
    "service_total": 50.00,
    "form_print_price": 25.00,
    "status": "normal",
    "contact_type": "rent_land",
    "currency": "ກີບ",
    "province_id": 1,
    "province_name": "ນະຄອນຫຼວງວຽງຈັນ",
    "days_overdue": 10,
    "calculated_late_fee": 60.00,
    "subtotal": 400.00,
    "grand_total": 535.00
  }
}
```

### Endpoint-Specific Errors

| HTTP | Code | When |
|------|------|------|
| 400 | `INVALID_INPUT` | Invalid or non-positive `id` |
| 404 | `NOT_FOUND` | Invoice not found or not a rent-land invoice |
| 500 | `INTERNAL_ERROR` | Failed to retrieve invoice |

### Usage

```bash
curl -X GET "http://localhost:5525/api/v1/public/income/rent-land/invoices/42" \
  -H "X-API-Key: your-api-key-here"
```

### Business Logic

1. Validate API key and path `id`
2. Load invoice from `as_income_invoice` where `id` matches, `contact_type = 'rent_land'`, and `invoice_type = 'invoice'`
3. Join `as_provinces` on `province_id` for `province_name`
4. Compute late fee from parent contract when status is `normal` and overdue

---

## Data Model

### RentLand Object

| Field | Type | Nullable | Description |
|-------|------|----------|-------------|
| `contact_id` | integer | No | Primary key |
| `contract_type` | string | No | `concession` or `rental` |
| `business_type_id` | integer | Yes | FK to `as_rent_land_business_types.id` |
| `business_type_name` | string | Yes | Lao label from lookup table |
| `contact_number` | string | Yes | Contract number |
| `contact_name` | string | No | Contract name |
| `contact_date_start` | string | Yes | Start date `YYYY-MM-DD` |
| `contact_date_end` | string | Yes | End date `YYYY-MM-DD` |
| `payment_type_id` | integer | No | Income type ID |
| `payment_type_name` | string | Yes | Income type name |
| `partner` | string[] | No | Partner names |
| `lessor` | string[] | No | Lessor names (concession) |
| `type_contact` | string[] | No | Company types (concession) |
| `rent_year` | integer | No | Contract duration in years |
| `year_start_payment` | string | No | First payment year |
| `set_payment_date` | string | Yes | Payment due date `YYYY-MM-DD` (day now significant for yearly late-fee due dates) |
| `before_payment` | string | Yes | Days before due date |
| `contect_price` | number | No | Total contract price |
| `payment_frequency` | string | Yes | `monthly` or `yearly` |
| `payment_amount` | number | Yes | Payment amount per period |
| `late_fee_type` | string | Yes | `percentage` or `amount` |
| `late_fee_value` | number | Yes | Late fee value |
| `rent_to_year` | array | No | Yearly price increase schedule |
| `from_province_id` | integer | No | Budget province ID |
| `from_province_name` | string | Yes | Budget province name |
| `partner_address` | object | No | Partner company address |
| `organization` | object | No | Managing organization hierarchy |
| `rental_format` | string | Yes | Rental format (rental only) |
| `status` | string | No | `nomal`, `cancel`, or `end` |
| `file` | string | No | Attached file name |
| `file_url` | string | Yes | S3 file URL |
| `file_s3_uploaded` | boolean | No | File uploaded to S3 |
| `created_by` | object | Yes | Creator user info |
| `created_at` | string | No | ISO 8601 timestamp |
| `updated_at` | string | No | ISO 8601 timestamp |
| `items` | array | No | Land plot items |

### RentLandItem Object

| Field | Type | Nullable | Description |
|-------|------|----------|-------------|
| `id` | integer | No | Item ID |
| `contact_land_id` | integer | No | Parent contract ID |
| `rent_detell` | string | No | Land use detail |
| `province_id` | integer | No | Land province ID |
| `province_name` | string | Yes | Land province name |
| `dristric_id` | string | No | Land district ID |
| `district_name` | string | Yes | Land district name |
| `village` | string | No | Village |
| `detail_addess` | string | No | Detail address |
| `area` | number | Yes | Plot area |
| `area_land` | number | Yes | Land area (concession) |
| `area_build` | number | Yes | Building area (concession) |
| `unit` | string | Yes | Area unit |
| `rent_m_price` | number | No | Rent price per unit |
| `total_project` | number | No | Total project value |
| `currency` | string | No | Currency |
| `detell` | string | Yes | Additional detail |
| `latitude` | string | Yes | GPS latitude |
| `longitude` | string | Yes | GPS longitude |

**Notes:**
- `partner`, `lessor`, `type_contact` are stored as `+`-separated strings in DB; API returns JSON arrays
- Concession-only fields may be empty on rental contracts
- List responses include the same full object shape as detail

### Invoice Object

| Field | Type | Nullable | Description |
|-------|------|----------|-------------|
| `id` | integer | No | Invoice ID |
| `invoice_number` | string | No | Generated invoice number |
| `contact_id` | integer | No | Parent rent-land contract ID |
| `partner` | string | No | Partner / payer name |
| `date_payment` | string | No | Due date `YYYY-MM-DD` |
| `total` | number | No | Rent subtotal (line items sum) |
| `service_total` | number | No | Service fee (ຄ່າບໍລິການ) |
| `form_print_price` | number | No | Form print fee (ຄ່າແບບພິມ) |
| `status` | string | No | `normal`, `payment`, or `cancel` |
| `contact_type` | string | No | Always `rent_land` for rent-land invoices |
| `currency` | string | No | Currency code |
| `province_id` | integer | Yes | Budget province ID |
| `province_name` | string | Yes | Budget province name (from join) |
| `reference_no` | string | Yes | Reference numbers (JSON array string) |
| `days_overdue` | integer | Yes | Days past due (unpaid invoices only) |
| `calculated_late_fee` | number | Yes | Computed late fee (not stored in DB) |
| `subtotal` | number | No | Same as `total` |
| `grand_total` | number | No | Full amount due including fees |

---

## Error Code Reference

| Error Code | HTTP | When | Resolution |
|------------|------|------|------------|
| `API_KEY_INVALID` | 401 | Missing, unknown, or domain-restricted key | Provide valid `X-API-Key` |
| `API_KEY_INACTIVE` | 403 | Key deactivated | Request new active key |
| `API_KEY_EXPIRED` | 403 | Key past expiration | Request renewed key |
| `INVALID_INPUT` | 400 | Bad query/path params (`type`, `contract_type`, `from_province_id`, `id`, `status`, `contact_id`) | Fix query/path values |
| `NOT_FOUND` | 404 | Contract or invoice ID not found | Verify ID from list |
| `INTERNAL_ERROR` | 500 | Server or database error | Retry; contact support |

---

## Best Practices

1. Store the API key securely; do not expose it in client-side code
2. Use the list endpoint with pagination for browsing; use detail for a single contract
3. Cache `type_contacts`, `contract_types`, and quantity responses — they change infrequently
4. Fetch type contacts once for filter dropdown UI
5. Fetch contract types once for contract type filter dropdown UI
6. Use `contract_type` to show/hide concession vs rental fields in the client
7. Treat empty arrays and `quantity: 0` as valid responses, not errors
8. Use invoice list with `contact_id` filter to show billing history for a specific contract

---

## Testing

| # | Scenario | Expected |
|---|----------|----------|
| 1 | Valid API key → quantity | 200 with `data.quantity` |
| 2 | Valid API key → list | 200 with paginated `rent_lands` |
| 3 | Valid API key → detail (existing id) | 200 with `rent_land` and `items[]` |
| 4 | Valid API key → type-contacts | 200 with `type_contacts` array |
| 5 | Valid API key → contract-types | 200 with `contract_types` array of `{code, label}` |
| 6 | Invalid `type` or `contract_type` on list | 400 |
| 7 | Non-existent contract id | 404 |
| 8 | Missing API key | 401 |
| 9 | No matching list filters | 200 with `rent_lands: []`, `total: 0` |
| 10 | Valid API key → invoice list | 200 with paginated `invoices` |
| 11 | Valid API key → invoice detail (existing id) | 200 with `invoice` including totals and `province_name` |
| 12 | Invalid `status` on invoice list | 400 |
| 13 | Non-existent invoice id | 404 |

**Tools:** curl, Postman, Insomnia, HTTPie

**Insomnia collection:** Import `public-api-collection.insomnia.json` (not included)
