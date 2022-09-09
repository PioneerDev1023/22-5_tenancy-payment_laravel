{!! Form::model($requestdomain, ['route' => ['status.update', $requestdomain->id], 'method' => 'post', 'enctype' => 'multipart/form-data']) !!}

<div class="form-group ">
    {{ Form::label('reason', __('Reason')) }}
    <div class="input-group ">
        {!! Form::textarea('reason', null , ['class' => 'form-control', ' required', 'placeholder' => __('Enter Reason')]) !!}
    </div>
</div>
<div class="btn-flt">
    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
    <button type="sub" class="btn btn-primary">{{ __('Save') }}</button>
</div>
{!! Form::close() !!}
