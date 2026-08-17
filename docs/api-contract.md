# API contract

## Why a normalized contract exists

The frontend never consumes raw `WP_Post` objects or raw ACF arrays. Those shapes are editorial implementation details: media and relationship fields can return IDs, arrays, or objects depending on ACF settings, and raw WordPress values can expose fields that the public UI does not need.

`Headless_Catalog\Product_Normalizer` is the PHP boundary. It converts WordPress/ACF values into one explicit representation before `REST_Controller` sends JSON. `frontend/lib/schemas.ts` is the second boundary: Zod validates the external JSON at runtime, and `frontend/lib/wordpress.ts` returns typed values only after parsing succeeds. A breaking CMS change therefore fails at the data layer instead of reaching a React component as an unexpected value.

The endpoint namespace, `catalog/v1`, is the contract version. Additive optional fields may remain in v1. Renames, removals, required-field changes, or semantic changes require `catalog/v2` and a migration period.

## Single-product response

`GET /wp-json/catalog/v1/products/temperature-sensor-t100`

```json
{
  "id": 101,
  "slug": "temperature-sensor-t100",
  "title": "Temperature Sensor T100",
  "status": "publish",
  "excerpt": "Commercial temperature sensor for building automation.",
  "content": "<p>Designed for reliable field operation.</p>",
  "sku": "T100",
  "price": {
    "amount": 129.99,
    "currency": "USD",
    "formatted": "$129.99"
  },
  "featuredImage": null,
  "gallery": [],
  "specifications": [
    { "label": "Operating range", "value": "-20 F to 180 F" },
    { "label": "Output", "value": "4-20 mA" }
  ],
  "datasheetUrl": "https://example.com/datasheets/temperature-sensor-t100.pdf",
  "taxonomies": {
    "categories": [
      { "id": 2, "slug": "environmental-sensors", "name": "Environmental Sensors" }
    ],
    "manufacturers": [
      { "id": 3, "slug": "northstar-controls", "name": "Northstar Controls" }
    ],
    "applications": [
      { "id": 4, "slug": "cold-storage", "name": "Cold Storage" }
    ]
  },
  "relatedProducts": [
    {
      "id": 102,
      "slug": "pressure-transmitter-p220",
      "title": "Pressure Transmitter P220",
      "excerpt": "Commercial pressure transmitter for process monitoring.",
      "sku": "P220",
      "price": { "amount": 184.5, "currency": "USD", "formatted": "$184.50" },
      "featuredImage": null
    }
  ],
  "publishedAt": "2026-08-17T12:00:00+00:00",
  "modifiedAt": "2026-08-17T12:15:00+00:00"
}
```

Every key shown above is returned by the implementation. Related products intentionally use the smaller summary shape shown above and do not contain their own taxonomies, specifications, content, or relationships.

## Collection response and pagination

`GET /wp-json/catalog/v1/products?page=1&per_page=2&sort=price&order=asc`

```json
{
  "data": [
    {
      "id": 109,
      "slug": "signal-isolator-si2",
      "title": "Signal Isolator SI2",
      "status": "publish",
      "excerpt": "Commercial signal isolator for process monitoring.",
      "content": "<p>Designed for reliable field operation.</p>",
      "sku": "SI2",
      "price": { "amount": 98, "currency": "USD", "formatted": "$98.00" },
      "featuredImage": null,
      "gallery": [],
      "specifications": [],
      "datasheetUrl": null,
      "taxonomies": {
        "categories": [],
        "manufacturers": [],
        "applications": []
      },
      "relatedProducts": [],
      "publishedAt": "2026-08-17T12:00:00+00:00",
      "modifiedAt": "2026-08-17T12:00:00+00:00"
    }
  ],
  "pagination": { "page": 1, "perPage": 2, "total": 12, "totalPages": 6 }
}
```

The example shortens the `data` array to one product for readability; each element uses the complete single-product shape.

| Parameter | Validation and behavior |
| --- | --- |
| `page` | Integer, minimum 1; default 1 |
| `per_page` | Integer, 1-50; default 12 |
| `search` | Sanitized text, maximum 100 characters |
| `category` | Optional taxonomy slug |
| `manufacturer` | Optional taxonomy slug |
| `application` | Optional taxonomy slug |
| `sort` | `date`, `modified`, `title`, or `price` only |
| `order` | `asc` or `desc` only |

The PHP controller maps `sort` through an explicit map before constructing `WP_Query`. Arbitrary query order fields cannot pass through.

## Field rules

- `featuredImage` and `datasheetUrl` are nullable.
- `gallery`, `specifications`, every taxonomy collection, and `relatedProducts` are always arrays, including when empty.
- No response property is optional in the current v1 Zod schema.
- Image objects contain only `url`, `alt`, `width`, and `height`.
- Taxonomy terms contain only numeric `id`, `slug`, and display `name`.
- `price.amount` is a non-negative JSON number; `currency` is a three-letter uppercase code; `formatted` is display text produced by WordPress.
- `publishedAt` and `modifiedAt` are ISO 8601 strings with a UTC offset.
- `content` is WordPress-sanitized HTML and is allow-listed again on the Next.js server before rendering.

## Error envelope

Application errors use `WP_Error`, which WordPress serializes consistently:

```json
{
  "code": "catalog_product_not_found",
  "message": "Product not found.",
  "data": { "status": 404 }
}
```

Invalid route arguments use WordPress REST validation errors such as `rest_invalid_param` with HTTP `400`. Missing products and unauthorized draft requests intentionally share the same `404` envelope so the public API does not disclose draft existence.

## Preview contract

The public collection only queries `post_status=publish`. The single endpoint accepts `?preview=true`, but an unpublished product is returned only when WordPress authenticates the server-to-server request and `current_user_can( 'edit_post', $post->ID )` succeeds. The public browser never receives the Application Password.
