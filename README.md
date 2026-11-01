# Captcha QA 2.1.1 — Joomla 5.4 / 6

Community-maintained modification of [Captcha QA by Ron Crans / RJCreations](https://github.com/ron4mac/joomla_plg_qa) version 1.5.5. **Not an official RJCreations release.**

## Installation

Download the installable ZIP from GitHub **Releases** and use Joomla System → Install → Extensions. **Do not uninstall the prior `captcha/qa` plugin.** This package updates the same plugin identity and is intended to preserve existing configuration and user-created question files in `media/plg_captcha_qa/custom/`. Back up the site first.

## Compatibility

Tested by a community user with Joomla 5.4.10, Joomla 6 with *Behaviour – Backward Compatibility 6* disabled, and Kunena 7.1.0 (Captcha on Joomla 5). Also tested with Joomla registration and Akeeba Engage on Joomla 5. Joomla 6 registration, answer checking, question editing and minimum response time were tested. Not all extensions or attack scenarios have been tested.

## Timing settings

- **Minimum response time (`time_check`)**: default 10 seconds. A CAPTCHA challenge must be at least this old when a correct answer is submitted. A wrong answer reports an answer error first.
- **Cooldown after successful CAPTCHA (`success_cooldown`)**: default 300 seconds (5 minutes). After a successful check, another *successful* check in the same Joomla session is rejected until this period ends. Applies across all forms using QA. Incorrect answers do not reset or extend the cooldown. The cooldown error displays the remaining time in seconds.
- Set either value to **0** to disable that check. Changing Joomla session / clearing session cookies can reset the cooldown; it is intentionally not IP- or fingerprint-based.
- Cooldown begins at CAPTCHA success, not when the form action (e.g. registration) is completed.

## Changes from upstream 1.5.5

- Modern `CaptchaProviderInterface` with `onCaptchaSetup`.
- HMAC-SHA256-signed challenge tied to Joomla session, one-hour expiry and one-time acceptance.
- Protect question editor with administrator permission and POST CSRF checks; constrain custom filenames and input.
- Remove debug file writes; escape question filename output.
- Remove upstream update URL; keep original plugin ID `captcha/qa` for in-place upgrade.

## Files

The repository root contains the Joomla install package sources (`qa.xml`, `src/`, `services/`, `media/`, `language/`, `layouts/`, `script.php`). The installable release ZIP contains these files directly in its top level, **not** within a parent folder.

## License and credits

GPL-3.0-or-later (see `LICENSE.txt`), preserving original attribution and license notices. Original code © RJCreations / Ron Crans. Modifications are provided as a community fork and are not endorsed by the original author. Contributions welcome.
