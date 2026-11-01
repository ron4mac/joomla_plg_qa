# Changelog

## 2.1.1 — 2026-11-01

- Show the remaining wait time (in seconds) in the session cooldown error.
- Updated German and English translations; no changes to cooldown enforcement.

## 2.1.0 — 2026-11-01

- Added configurable session-only cooldown between successful CAPTCHA checks (default 300 seconds, 0 disables).
- Kept existing configurable minimum response time (default 10 seconds).
- Added German and English backend labels and cooldown error messages.


## 2.0.1 — 2026-11-01

- Validate the CAPTCHA answer before the minimum-response-time check, to show the correct error for incorrect answers.
- Keep the timing check and one-time acceptance for correct answers.

## 2.0.0 — 2026-11-01

- Converted legacy CAPTCHA event handlers to Joomla CAPTCHA provider interface.
- Added HMAC-signed session-bound challenges, validation and replay protection.
- Hardened administrator AJAX question editing with ACL, CSRF, filename and data checks.
- Escaped custom question filenames in administrator form.
- Removed development debug-file writes and upstream update metadata.
- Kept compatibility with existing custom question directory and plugin identity.

Based on RJCreations Captcha QA 1.5.5.
