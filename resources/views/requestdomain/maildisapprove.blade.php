@component('mail::message')
<div class="section-body">
    <div class="row">
    <div class="card col-md-6 mx-auto">
    <div class="card-body">
<h3>Hi {{$domain_details->name}},</h3>
<br>
<p>Yout Domain <b>{{$domain_details->domain_name}}</b> is not Verified By Super Admin.</p>
<p>Reason: {{$domain_details->reason}}</p>
<p> Please contact to Super Admin</p>

</div>
</div>
</div>

{{__('Thanks,')}}<br>
{{ config('app.name') }}
@endcomponent
