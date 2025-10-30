<div class="email-wrapper">
    <div class="email-body" role="article" aria-roledescription="email">
      <div class="email-header">
        <h1>Order Status Update</h1>
      </div>

      <div class="email-content">
        <p>Hello {{ $order->user->name }},</p>

        <p>Your order <strong>#{{ $order->order_reference ?? $order->id }}</strong> status has been updated to:</p>

        <div class="status">{{ $order->order_status }}</div>

        <div style="margin-top:12px;">
          @switch($order->order_status)
            @case('Order Placed')
              <p>We have received your order and it’s being reviewed.</p>
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

        <hr style="border:none;border-top:1px solid #f0f0f0;margin:20px 0;">

        <p class="muted">If you did not place this order or you think this is an error, please contact our support.</p>
      </div>

      <div class="footer">
        <div>{{ config('app.name') }}</div>
        <div class="muted">© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</div>
      </div>
    </div>
  </div>