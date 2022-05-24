@php
use Carbon\Carbon;
@endphp
@extends('layouts.main')
@section('title', __('Plans'))
@section('content')

        <div class="main-content">
            <section class="section">
                <div class="section-header">
                    <h1>{{ __('Plans List') }}</h1>
                    <div class="section-header-breadcrumb">
                        <div class="breadcrumb-item active"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></div>
                        <div class="breadcrumb-item">{{ __('Plans') }}</div>
                    </div>
                </div>
                <div class="section-body">
                    <div class="row ">
                        <div class="col-12">
                            <div class="card p-3">
                                <div class="table-responsive py-4">
                                    {{ $dataTable->table(['width' => '100%']) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>


@endsection
@hasrole('Admin')
    @push('css')
        @include('layouts.includes.datatable_css')
    @endpush
    @push('javascript')
        @include('layouts.includes.datatable_js')
        {{ $dataTable->scripts() }}
    @endpush
@endhasrole
@hasrole('Admin')

    @push('javascript')
        <script>
            $('body').on('shown.bs.modal', '#common_modal', function() {
                var stripe = Stripe('{{ Utility::getsettings('STRIPE_KEY') }}');
                var elements = stripe.elements();
                // Custom Styling
                var style = {
                    base: {
                        color: '#32325d',
                        lineHeight: '24px',
                        fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
                        fontSmoothing: 'antialiased',
                        fontSize: '16px',
                        '::placeholder': {
                            color: '#aab7c4'
                        }
                    },
                    invalid: {
                        color: '#fa755a',
                        iconColor: '#fa755a'
                    }
                };

                // Create an instance of the card Element
                var card = elements.create('card', {
                    style: style
                });

                // Add an instance of the card Element into the `card-element` <div>
                card.mount('#card-element');

                // Handle real-time validation errors from the card Element.
                card.addEventListener('change', function(event) {
                    var displayError = document.getElementById('card-errors');

                    if (event.error) {
                        displayError.textContent = event.error.message;
                    } else {
                        displayError.textContent = '';
                    }
                });

                // Handle form submission
                var form = document.getElementById('payment-form');

                form.addEventListener('submit', function(event) {

                    event.preventDefault();

                    stripe.createToken(card).then(function(result) {
                        if (result.error) {
                            // Inform the user if there was an error
                            var errorElement = document.getElementById('card-errors');
                            errorElement.textContent = result.error.message;
                        } else {
                            stripeTokenHandler(result.token);
                        }
                    });
                });

            })

            $('body').on('click', '.subscribe_plan', function() {
                var plan_id = $(this).data('id');
                var plan_amount = $(this).data('amount');
                var modal = $('#common_modal');
                $.ajax({
                    type: "GET",
                    url: '{{ route('stripe.pay') }}',
                    data: {},
                    success: function(response) {
                        modal.find('.modal-title').html('{{ __('Payment') }}');
                        modal.find('.modal-body').html(response.html);
                        $('#pay_btn').text('Pay {{ Utility::getsettings('currency_symbol') }}' +
                            plan_amount)
                        $('#plan_id').val(plan_id)
                        modal.modal('show');
                    },
                    error: function(error) {}
                });
            });
        </script>

        <script type="text/javascript" src="https://js.stripe.com/v3/"></script>
        <script>
            // Send Stripe Token to Server
            function stripeTokenHandler(token) {
                // Insert the token ID into the form so it gets submitted to the server
                var form = document.getElementById('payment-form');

                // Add Stripe Token to hidden input
                var hiddenInput = document.createElement('input');
                hiddenInput.setAttribute('type', 'hidden');
                hiddenInput.setAttribute('name', 'stripeToken');
                hiddenInput.setAttribute('value', token.id);
                form.appendChild(hiddenInput);

                // Submit form
                form.submit();
            }
        </script>
    @endpush
@endhasrole
