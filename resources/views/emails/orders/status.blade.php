<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: Arial, sans-serif;
      background-color: #f4f4f4;
    }
    .email-wrapper {
      width: 100%;
      background-color: #f4f4f4;
      padding: 20px 0;
    }
    .email-container {
      max-width: 600px;
      margin: 0 auto;
      background-color: #ffffff;
      overflow: hidden;
    }
    .email-header {
      background-color: #6B1C1C;
      color: #ffffff;
      padding: 30px 20px;
      text-align: center;
    }
    .logo {
      font-size: 28px;
      font-weight: bold;
      margin: 0;
      letter-spacing: 1px;
    }
    .email-content {
      padding: 40px 30px;
      color: #333333;
      line-height: 1.6;
    }
    .email-content h1 {
      color: #6B1C1C;
      font-size: 24px;
      margin-top: 0;
    }
    .status {
      background-color: #6B1C1C;
      color: #ffffff;
      padding: 12px 20px;
      border-radius: 5px;
      display: inline-block;
      font-weight: bold;
      margin: 15px 0;
    }
    .btn {
      display: inline-block;
      background-color: #6B1C1C;
      color: #ffffff;
      padding: 12px 30px;
      text-decoration: none;
      border-radius: 5px;
      font-weight: bold;
      margin-top: 10px;
    }
    .btn:hover {
      background-color: #8B2323;
    }
    .muted {
      color: #888888;
      font-size: 13px;
    }
    hr {
      border: none;
      border-top: 1px solid #e0e0e0;
      margin: 25px 0;
    }
    .footer {
      background-color: #6B1C1C;
      color: #ffffff;
      padding: 25px 30px;
      text-align: center;
      font-size: 14px;
    }
    .footer-muted {
      color: #d4a5a5;
      font-size: 12px;
      margin-top: 8px;
    }
  </style>
</head>
<body>
  <div class="email-wrapper">
    <div class="email-container">
      <!-- Header with Logo -->
      <div class="email-header">
        <div class="logo">{{ config('app.name') }}</div>
      </div>

      <!-- Email Content -->
      <div class="email-content">
        <h1>Order Status Update</h1>
        
        <p>Hello {{ $order->user->name }},</p>

        <p>Your order <strong>#{{ $order->order_reference ?? $order->id }}</strong> status has been updated to:</p>

        <div class="status">{{ $order->order_status }}</div>

        <div style="margin-top:12px;">
          @switch($order->order_status)
            @case('Order Placed')
              <p>We have received your order and it's being reviewed.</p>
              @break

            @case('Processing')
              <p>Your payment has been confirmed. We are preparing your items.</p>
              @break

            @case('Packed')
              <p>Your items are packed and ready to ship.</p>
              @break

            @case('Shipped')
              <p>Your order has now been dispatched and is making its way to you with our trusted carrier</p>
              @break

            @case('Out for Delivery')
              <p>Your courier is currently delivering your package.</p>
              @break

            @case('Delivered')
              <p>We delivered your order today, {{ $order->updated_at->format('F j, Y') }}. We hope you like your purchase</p>
              @break

            @default
              <p>We updated the status of your order. If you have any questions, reply to this email.</p>
          @endswitch
        </div>

        <p style="margin-top:18px;">
          <a class="btn" href="{{ config('app.url') . '/orders/' . $order->id }}" target="_blank" rel="noopener">View Order</a>
        </p>

        <hr>

        <p class="muted">If you did not place this order or you think this is an error, please contact our support.</p>
      </div>

      <!-- Footer -->
      <div class="footer">
        <div>{{ config('app.name') }}</div>
        <div class="footer-muted">© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</div>
      </div>
    </div>
  </div>
</body>
</html>