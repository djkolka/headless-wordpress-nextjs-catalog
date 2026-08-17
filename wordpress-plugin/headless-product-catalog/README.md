# Headless Product Catalog plugin

This plugin owns the `hpc_product` content model and exposes only the normalized `/wp-json/catalog/v1` contract. ACF Pro is the primary authoring dependency and is not bundled. The plugin can also operate with compatible field APIs where available. Without a compatible field API, seeded metadata remains readable through the fallback repository layer, but the complete editing UI is unavailable.

Install the folder in `wp-content/plugins`, activate `headless-product-catalog`, and run `wp catalog seed`. Configure `HPCATALOG_REVALIDATION_URL` and `HPCATALOG_REVALIDATION_SECRET` as environment variables or constants in `wp-config.php` to enable signed frontend invalidation.
