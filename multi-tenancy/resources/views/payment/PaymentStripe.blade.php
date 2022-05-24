<form role="form" action="{{ route('stripe.post') }}" method="post" class="require-validation" id="payment-form">
    @csrf

    <div id="card-element" class="form-control">

        <!-- a Stripe Element will be inserted here. -->
    </div>
    <span id="card-errors" class="payment-errors"></span>
    <br>
    <div class="row">
        <div class="col">
            <input type="hidden" name="plan_id" id="plan_id">
            <button id="pay_btn" class="btn btn-primary btn-lg " type="submit">{{ __('Pay Now') }}
            </button>
        </div>
    </div>
</form>
