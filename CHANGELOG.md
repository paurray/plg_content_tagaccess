# Changelog

All notable changes to Content - Tag Access are documented here.
Format follows [Keep a Changelog](https://keepachangelog.com/); versions follow the plugin manifest.

## [1.0.4] - 2026-07-17

### Added
- Optional `menuitem="N"` attribute: content renders only on the page whose active menu item ID matches. Combined with `id`, one tag now gates WHO and WHERE.
- Frontend-only guard for the menu item check (`isClient('site')`).

### Changed
- Fallback text now shows only for a WHO failure on a matching page; wrong-page misses render nothing.

## [1.0.3] - 2026-07-17

### Changed
- Author metadata set to Paul Staub.

## [1.0.2] - 2026-07-17

### Changed
- services/provider.php aligned with the core Joomla 6.1 pattern: config-only constructor plus lazy instantiation, verified against core source (plugins/content/joomla and CMSPlugin on the 6.1.1 tag). The previous dispatcher-first style worked via a deprecated compatibility branch that is removed in Joomla 7.

## [1.0.1] - 2026-07-17

### Fixed
- Added the missing `services/provider.php`. Version 1.0.0 installed and enabled cleanly but was never instantiated by Joomla - it silently did nothing. Modern namespaced plugins require the service provider bootstrap.

## [1.0.0] - 2026-07-15

### Added
- Initial build: `{accesslevel id="X"}...{/accesslevel}` content gating by Joomla Access Level via onContentPrepare, with optional fallback text parameter.
