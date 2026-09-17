# Zalo Webhook diagnostics - TDD evidence

Date: 2026-09-17

## User journeys

1. A site administrator can test the Zalo Bot, configured Webhook URL, and endpoint without a Chat ID or a message being sent.
2. A site administrator can select a private-chat Chat ID after the sender has sent any message to the bot, while group chats remain unavailable for appointment notifications.
3. Diagnostic state must not expose a Bot Token, Webhook Secret, or message body; Chat ID is visible only in the administrator recipient list.

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
| A Zalo recipient refusal is mapped to a safe, actionable private-chat re-opt-in message | Same command | PASS |
| Group chats and legacy candidates without a private-chat confirmation cannot receive notifications | Same command | PASS |
| Existing recipient candidates prompt for Chat ID even when there is no new event yet | Same command | PASS |
| Authenticated probes, private inbound messages, and duplicates produce safe progress metadata | Same command | PASS |
| Any authenticated private inbound message saves its Chat ID without retaining its content | Same command | PASS |
| Raw message content is absent from progress metadata | Same command | PASS |
| PHP source and test files parse | `php -l` for the three changed PHP files | PASS |

The standalone PHP harness has no coverage reporter; coverage percentage is not available. It exercises the new diagnostic success, failure, and privacy paths with simulated HTTP responses and never makes a network request or sends a real Zalo message.
