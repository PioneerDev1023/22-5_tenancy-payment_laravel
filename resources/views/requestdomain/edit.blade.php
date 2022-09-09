@extends('layouts.main')
@section('title', __('Domain Request'))

@section('content')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>{{ __('Edit User') }}</h1>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></div>
                    <div class="breadcrumb-item active"><a href="{{ route('requestdomain.index') }}">{{ __('Domain Request') }}</a></div>
                    <div class="breadcrumb-item">{{ __('Edit User') }}</div>
                </div>
            </div>
            <div class="section-body">
                <div class="row">
                    <div class="col-md-4 m-auto">
                        <div class="card p-4">
                            {!! Form::model($requestdomain, ['route' => ['create.user'], 'method' => 'POST', 'enctype' => 'multipart/form-data']) !!}
                            <div class="form-group ">
                                {{ Form::label('name', __('Name')) }}
                                <div class="input-group ">
                                    <div class="input-group-prepend">
                                        <div class="input-group-text">
                                            <i class="fas fa-user"></i>
                                        </div>
                                    </div>
                                    {!! Form::text('name', null, ['class' => 'form-control', ' required', 'placeholder' => __('Enter Name')]) !!}
                                </div>
                            </div>
                            <div class="form-group">
                                {{ Form::label('email', __('Email')) }}
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <div class="input-group-text">
                                            <i class="fas fa-envelope"></i>
                                        </div>
                                    </div>
                                    {!! Form::text('email', null, ['class' => 'form-control', ' required', 'placeholder' => __('Enter Email Address')]) !!}
                                </div>
                            </div>
                            <div class="form-group">
                                {{ Form::label('domains', __('Domain configration')) }}
                                <div class="input-group ">
                                    {!! Form::text('domains', isset($requestdomain->domain_name) ? $requestdomain->domain_name : '', ['class' => 'form-control', ' required', 'placeholder' => __('Enter domain name')]) !!}
                                </div>
                                <span>{{ __('how to add-on domain in your hosting panel.') }}<a
                                        href="{{ Storage::url('pdf/adddomain.pdf') }}" class="m-2"
                                        target="_blank">{{ __('Document') }}</a></span>
                            </div>
                            <div class="form-group">
                                {{ Form::label('db_name', __('Database Name')) }}
                                <div class="input-group ">
                                    {!! Form::text('db_name', null, ['class' => 'form-control', ' required', 'placeholder' => __('Enter Database Name')]) !!}
                                </div>
                            </div>
                            <div class="form-group">
                                {{ Form::label('db_username', __('Database User')) }}
                                <div class="input-group ">
                                    {!! Form::text('db_username', null, ['class' => 'form-control', ' required', 'placeholder' => __('Enter Database Username')]) !!}
                                </div>
                            </div>
                            <div class="form-group ">
                                {{ Form::label('db_password', __('Database Password:')) }}
                                <div class="input-group ">
                                    <div class="input-group-prepend">
                                    </div>
                                </div>
                                {!! Form::password('db_password', ['class' => 'form-control', ' required', 'placeholder' => __('Enter Database Password')]) !!}
                            </div>
                            <input type="hidden" name="type" value="{{ $requestdomain->type }}">
                            <input type="hidden" name="password" value="{{ $requestdomain->password }}">
                            <div class="btn-flt">
                                <a href="{{ route('requestdomain.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                                <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                            </div>
                            {!! Form::close() !!}
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
