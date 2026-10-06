# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.0] - 2026-10-06

### Added

- `TimeClient` for the Time REST API (`/api/v4`), constructed from plain values and usable without Laravel.
- Resources: posts (create, ephemeral, get, by channel, by ids, update, delete), reactions (save, delete), files (upload, info, download), channels (get, for user) and users (get).
- Data objects that validate input before a request is sent.
- `TimePostAttachments`: attachment references from a post without requests, and `TimeClient::attachments()->resolveMissing()` to fill in missing names and sizes.
- `DirectChannel::isDirectChannelName()`.
- `TimeApiException` with `status()`, `body()` and `json()`, and `TimeConnectionException` for connection failures.
- Separate configurable timeout for file downloads, 90 seconds by default.
- Laravel integration: auto-discovered service provider, `Time` facade, `time-messenger` config publishable with the `time-messenger-config` tag, and support for `Http::fake()`.

[Unreleased]: https://github.com/fenestron/laravel-time-messenger/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/fenestron/laravel-time-messenger/releases/tag/v0.1.0
