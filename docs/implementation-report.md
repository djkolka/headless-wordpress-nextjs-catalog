# Implementation report

## Delivered scope

The repository implements a sanitized WordPress-backed product catalog with a PHP-registered ACF Pro content model, a normalized `catalog/v1` REST API, an idempotent WP-CLI seed command, and a typed Next.js App Router frontend. It includes secure draft preview, signed targeted revalidation, ISR fallback, URL-driven filtering, SEO output, accessible UI states, tests, and reproducible CI definitions.

## Verified locally

- WordPress 7.0 installation confirmed before content changes.
- Custom plugin activated on the disposable local site.
- ACF Pro 6.8.6 was active and Secure Custom Fields was disabled for the final verification pass.
- Twelve fictional products created; repeat seeding updated the same twelve without duplicates.
- Live collection and detail endpoints returned `200`.
- Invalid parameter requests returned `400`.
- Missing and unauthenticated draft products returned `404`.
- Repeated collection requests produced a catalog cache hit.
- All plugin and test PHP files passed syntax checks.
- WordPress Core and Extra coding standards passed.
- Composer audit reported no advisories.
- Six frontend tests passed across schema, URL, loading, empty, and error behavior.
- ESLint, Prettier, strict TypeScript, and the Next.js 16.3.1 production build passed.
- npm audit reported no vulnerabilities.
- Production-server smoke tests rendered the real listing, filtered state, and detail page against local WordPress; signed revalidation succeeded and invalid preview entry was rejected.
- Repository and existing Git history scans found no credentials, private keys, proprietary names, or committed environment files.

## Not executed locally

The PHPUnit test suite was not pointed at the Windows development database because the official WordPress PHPUnit environment was unavailable locally. The same committed suite passed in GitHub Actions against WordPress 7.0 and an isolated MySQL 8.4 database. Local PHP syntax checks, coding standards, live REST integration checks, and draft-access verification passed independently.

## External dependencies and limitations

- ACF Pro is the primary field-management dependency and is not redistributed.
- The plugin can operate with compatible field APIs where available, but ACF Pro is the documented target for this code sample.
- A hosted demo is intentionally outside the deliverable.
- Signed webhook delivery is non-blocking and has no persistent retry queue; five-minute ISR bounds stale content.
- Prices are display-only and the project contains no commerce workflow.
