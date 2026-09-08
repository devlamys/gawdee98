# Commerce integrations

Configure all credentials in **Admin → Integrations**. Secrets are encrypted in the existing settings store and password fields never render stored values.

## Razorpay

1. Save the Key ID, Key Secret, and a dedicated webhook secret.
2. In Razorpay, register `/api/razorpay-webhook.php` and subscribe to `payment.captured`, `payment.failed`, and `order.paid`.
3. Checkout signatures are verified server-side. The backend then fetches the payment and requires the Razorpay order ID, INR amount, and `captured` state to match before marking the local order paid.

## Delhivery One

1. Create the pickup location in Delhivery One first.
2. Save the matching pickup-location name, warehouse address, dimensions, and the token for the selected staging or production environment.
3. Enable Delhivery. Admin order dispatch will then show **Book with Delhivery**. Booking performs a pincode serviceability check before manifestation.
4. Configure `/api/delhivery-webhook.php?token=YOUR_SHARED_TOKEN` in Delhivery using the same shared token saved in Admin. The handler accepts tracking updates idempotently.

Labels are proxied through the admin-only `admin/delhivery-label.php` endpoint, so the Delhivery token is never exposed to a browser.

## WhatsApp Cloud API

1. Save the Graph API version, phone-number ID, WhatsApp Business Account ID, permanent system-user access token, Meta app secret, and webhook verify token.
2. Register `/api/whatsapp-webhook.php` as the Meta callback. Subscribe to message status and inbound message events.
3. Create and obtain approval for the template names shown in Admin. The OTP template must be an authentication template with an OTP copy-code button.
4. Enable WhatsApp and the required OTP/order-notification switches.
5. Run `/cron/process-notifications.php?token=YOUR_CRON_TOKEN` every minute from Windows Task Scheduler or cron. Admin also has a manual **Process queue now** action.

Transactional placeholder order:

- Order/payment confirmed: customer name, order number, total.
- Packed: customer name, order number.
- Shipped: customer name, order number, courier, tracking reference.
- Delivered/cancelled: customer name, order number.
- OTP: the six-digit code.

Marketing messages are queued only for registered customers who explicitly ticked the WhatsApp marketing checkbox. The webhook treats `STOP`, `UNSUBSCRIBE`, `CANCEL`, and `OPT OUT` replies as consent withdrawal. Marketing still requires an approved Meta template and the Admin marketing switch.

For production, all webhook URLs must be publicly reachable over HTTPS; `localhost` URLs are only useful for local testing or a secure tunnel.
