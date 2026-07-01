# FarmQ — Egypt Market MVP

Soil & yield optimization system localized for the Egyptian agricultural market (bilingual EN/AR, EGP pricing, local payment rails).

## What's included

| Layer | Status |
|-------|--------|
| Auth, farms, app shell, tier gates | Done |
| CSV soil upload + crop selection | Done |
| Fertilization blueprint engine + export | Done |
| Geospatial map (NDVI, deficiency zones) | Done |
| Irrigation + weather forecast + alerts | Done |
| Historical tracking + agronomist portfolio + admin | Done |
| Wave 1 — Field-ready UX | Done |
| Wave 2 — Geometry & data quality | Done |
| **Wave 3 — Real geospatial pipeline** | **Done** |
| Wave 4 — Agronomist invites & portfolio | Done |
| Wave 5 — Alerts depth (governorate/crop-tuned, actions, digest) | Done |
| Billing — Paymob (EGP) + sandbox mode | Done |

## Quick start

```bash
cp .env.example .env
docker compose up --build
```

Open **http://localhost:8081**

Set `DEV_UNLOCK_PAID=1` to test paid features without billing.

## Billing (Paymob / EGP)

Per-farm Paid tier: **450 EGP / season** (configurable via `SUBSCRIPTION_PRICE_EGP` / `SUBSCRIPTION_DAYS`).

- **Sandbox mode (default):** leave `PAYMENT_GATEWAY_API_KEY` empty — checkout runs a built-in test gateway so the full flow works with no live keys.
- **Live mode:** set `PAYMENT_GATEWAY_API_KEY`, `PAYMOB_INTEGRATION_ID`, `PAYMOB_IFRAME_ID`, `PAYMOB_HMAC_SECRET`. Checkout uses the Paymob hosted iframe; the tier is activated by the HMAC-verified webhook at `POST /billing/webhook`.
- Rails offered: Fawry, Vodafone Cash, Meeza, InstaPay, card. Transactions recorded in `billing_transactions`.

> Note: with `DEV_UNLOCK_PAID=1`, paid features are unlocked regardless of tier. Set it to `0` to see real free/paid gating and exercise billing end-to-end.

### Existing databases — Wave 2 migration

```bash
Get-Content database/migrations/003_wave2_geometry.sql | docker compose exec -T db mysql -ufarmq -pfarmq_secret farmq
```

## Wave 3 — Real geospatial pipeline

- **Sentinel-2 STAC** — searches [Earth Search](https://earth-search.aws.element84.com) for latest low-cloud L2A scenes over your farm bbox
- **Python worker** — computes NDVI/NDRE from COG bands when rasterio is available; falls back to scene-linked estimates
- **Soil-based deficiency map** — N/P/K zones from latest soil sample + NDVI stress
- **Map UI** — layer toggles, scene ID, cloud cover, scan history, NDVI delta vs previous scan
- **Dashboard** — NDVI trend arrow, rescan hint after 14 days

### Run a scan

1. Draw farm boundary (`/farms/boundary`) for accurate bbox
2. Upload soil CSV (feeds deficiency zones)
3. Open **Map** → **Run satellite scan**
4. Worker processes via Redis; page auto-refreshes while queued

Rebuild worker after Wave 3:

```bash
docker compose up --build -d worker
```

## License

Proprietary — LogiQ Studio
