# Zalo Webhook diagnostics - TDD evidence

Date: 2026-09-17

## User journeys

1. A site administrator can test the Zalo Bot, configured Webhook URL, and endpoint without a Chat ID or a message being sent.
2. A site administrator can see whether the most recent authenticated request was an endpoint probe, a `/nhanlich` opt-in, an unrelated message, or a duplicate.
3. Diagnostic state must not expose a Bot Token, Webhook Secret, Chat ID, sender name, or message body.

## RED then GREEN

The diagnostic tests were added before the implementation. Running:

```text
php theme\eyecare-child\tests\booking-zalo-test.php
```

initially stopped with `Call to undefined function ec_zalo_diagnose_webhook()`, which is the intended missing behavior. After the implementation, the same command passed.

## Guarantees

| Guarantee | Test command | Result |
|---|---|---|
| Bot, URL, and endpoint checks report success independently | `php theme\eyecare-child\tests\booking-zalo-test.php` | PASS |
| Missing token makes no Zalo API request | Same command | PASS |
| URL mismatch and endpoint refusal are distinguishable | Same command | PASS |
| Authenticated probes, opt-ins, duplicates, and unrelated messages produce safe progress metadata | Same command | PASS |
| Raw message content is absent from progress metadata | Same command | PASS |
| PHP source and test files parse | `php -l` for the three changed PHP files | PASS |

The standalone PHP harness has no coverage reporter; coverage percentage is not available. It exercises the new diagnostic success, failure, and privacy paths with simulated HTTP responses and never makes a network request or sends a real Zalo message.
