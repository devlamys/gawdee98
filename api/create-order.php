<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/integrations.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    gawdee_json_response(['ok' => false, 'message' => 'Method not allowed.'], 405);
}

try {
    $payload = gawdee_request_json();
    gawdee_verify_csrf($payload['csrf_token'] ?? null);
    gawdee_expire_stale_payment_orders();
    $loggedCustomer = gawdee_customer();

    $customer = is_array($payload['customer'] ?? null) ? $payload['customer'] : [];
    $fields = [];
    foreach (['name', 'email', 'phone', 'address1', 'address2', 'city', 'state', 'pincode', 'notes'] as $field) {
        $fields[$field] = trim(mb_substr((string) ($customer[$field] ?? ''), 0, $field === 'notes' ? 500 : 180));
    }
    if ($loggedCustomer) {
        $fields['email'] = (string) $loggedCustomer['email'];
    }
    if (mb_strlen($fields['name']) < 2 || !filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Enter a valid name and email address.');
    }
    if (!preg_match('/^[0-9+()\s-]{8,18}$/', $fields['phone'])) {
        throw new RuntimeException('Enter a valid phone number.');
    }
    if ($fields['address1'] === '' || $fields['city'] === '' || $fields['state'] === '' || !preg_match('/^[1-9][0-9]{5}$/', $fields['pincode'])) {
        throw new RuntimeException('Enter a complete Indian delivery address and six-digit pincode.');
    }

    $requestedItems = is_array($payload['items'] ?? null) ? $payload['items'] : [];
    $paymentMethod = ($payload['payment_method'] ?? '') === 'cod' ? 'cod' : 'razorpay';
    if ($paymentMethod === 'cod' && gawdee_setting('cod_enabled', '1') !== '1') {
        throw new RuntimeException('Cash on delivery is not available.');
    }
    if ($paymentMethod === 'razorpay' && !gawdee_razorpay_configured()) {
        throw new RuntimeException('Online payment is being configured. Choose cash on delivery or contact the store.');
    }

    $checkoutToken = trim((string) ($payload['checkout_token'] ?? ''));
    $couponCode = trim((string) ($payload['coupon_code'] ?? ''));
    $order = gawdee_create_local_order(
        $fields,
        $requestedItems,
        $paymentMethod,
        $loggedCustomer ? (int) $loggedCustomer['id'] : null,
        $checkoutToken,
        $couponCode
    );

    if ($order['payment_method'] !== $paymentMethod) {
        throw new RuntimeException('This checkout session is already linked to another payment method. Refresh checkout to begin a new order.');
    }

    if ($paymentMethod === 'razorpay' && $order['payment_status'] !== 'paid') {
        if (in_array($order['payment_status'], ['failed', 'expired'], true)) {
            throw new RuntimeException('The previous payment attempt ended. Refresh checkout to start a new secure payment.');
        }
        if ($order['razorpay_order_id'] === '') {
            try {
                $razorpayOrder = gawdee_razorpay_create_order(
                    (int) $order['total'],
                    (string) $order['order_number'],
                    ['gawdee_order' => $order['order_number'], 'customer_email' => $fields['email']]
                );
                gawdee_attach_razorpay_order((int) $order['id'], (string) $razorpayOrder['id']);
                $order = gawdee_order_by_id((int) $order['id']) ?? $order;
            } catch (Throwable $paymentError) {
                gawdee_mark_payment_failed((int) $order['id'], $paymentError->getMessage());
                gawdee_log_integration('razorpay', 'initialize_checkout', 'failed', $paymentError->getMessage(), (string) $order['order_number']);
                throw new RuntimeException('Your order was received as ' . $order['order_number'] . ', but online payment could not start. It is visible to the store team; no payment was taken.');
            }
        }
    }

    $_SESSION['last_order_number'] = (string) $order['order_number'];
    $response = [
        'ok' => true,
        'order_number' => $order['order_number'],
        'payment_method' => $paymentMethod,
        'subtotal' => (int) $order['subtotal'],
        'discount' => (int) $order['discount'],
        'shipping' => (int) $order['shipping'],
        'total' => (int) $order['total'],
        'coupon_code' => $order['coupon_code'],
        'already_paid' => $order['payment_status'] === 'paid',
        'success_url' => 'order-success.php?order=' . rawurlencode((string) $order['order_number']),
        'account_url' => $loggedCustomer ? 'account-order.php?order=' . rawurlencode((string) $order['order_number']) : '',
    ];
    if ($paymentMethod === 'razorpay' && $order['payment_status'] !== 'paid') {
        $response['razorpay'] = [
            'key' => gawdee_setting('razorpay_key_id'),
            'order_id' => $order['razorpay_order_id'],
            'amount' => (int) $order['total'] * 100,
            'currency' => 'INR',
            'name' => gawdee_setting('store_name', 'Gawdee'),
            'description' => 'Order ' . $order['order_number'],
            'prefill' => ['name' => $fields['name'], 'email' => $fields['email'], 'contact' => $fields['phone']],
        ];
    }
    gawdee_log_integration('checkout', 'order_received', 'success', 'Order saved in the fulfilment queue.', (string) $order['order_number']);
    gawdee_process_notification_queue(5);
    gawdee_json_response($response);
} catch (Throwable $exception) {
    gawdee_log_integration('checkout', 'create_order', 'failed', $exception->getMessage());
    $resetCheckout = str_contains($exception->getMessage(), 'Your order was received as')
        || str_contains($exception->getMessage(), 'previous payment attempt ended');
    gawdee_json_response(['ok' => false, 'message' => $exception->getMessage(), 'reset_checkout' => $resetCheckout], 422);
}
