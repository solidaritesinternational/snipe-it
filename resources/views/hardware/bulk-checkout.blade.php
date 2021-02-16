@extends('layouts/default')

{{-- Page title --}}
@section('title')
{{ trans('admin/hardware/general.bulk_checkout') }}
@parent
@stop

{{-- Page content --}}
@section('content')

<style>
    .input-group {
        padding-left: 0px !important;
    }
</style>


<div class="row">
    <!-- left column -->
    <div class="col-md-7">
        <div class="box box-default">
            <div class="box-header with-border">
                <h2 class="box-title"> {{ trans('admin/hardware/general.bulk_checkout') }} </h2>
            </div>
            <div class="box-body">
                <form class="form-horizontal" method="post" action="{{ route('hardware/bulkcheckout') }}"
                    autocomplete="off">
                    {{ csrf_field() }}

                    <!-- Asset selector -->
                    @include ('partials.forms.edit.asset-select', [
                    'translated_name' => trans('general.assets'),
                    'fieldname' => 'selected_assets[]',
                    'multiple' => true,
                    'asset_status_type' => 'RTD',
                    'select_id' => 'assigned_assets_select',
                    ])

                    <!-- Checkout selector -->
                    @include ('partials.forms.checkout-selector', ['user_select' => 'true', 'location_select' =>
                    'true', 'required' => 'true'])
                    @include ('partials.forms.edit.checkout-user', ['required' => 'true'])
                    {{-- @include ('partials.forms.edit.asset-select', ['translated_name' => trans('general.asset'), 'fieldname' => 'assigned_asset', 'unselect' => 'true', 'style' => 'display:none;', 'required'=>'true']) --}}
                    @include ('partials.forms.edit.checkout-location', ['required'=>'true'])

                    <!-- Checkout/Checkin Date -->
                    <div class="form-group {{ $errors->has('checkout_at') ? 'error' : '' }}">
                        {{ Form::label('checkout_at', trans('admin/hardware/form.checkout_date'), array('class' => 'col-md-3 control-label')) }}
                        <div class="col-md-8">
                            <div class="input-group date col-md-5" data-provide="datepicker"
                                data-date-format="yyyy-mm-dd" data-date-end-date="0d">
                                <input type="text" class="form-control" placeholder="{{ trans('general.select_date') }}"
                                    name="checkout_at" id="checkout_at" value="{{ old('checkout_at') }}">
                                <span class="input-group-addon"><i class="fa fa-calendar" aria-hidden="true"></i></span>
                            </div>
                            {!! $errors->first('checkout_at', '<span class="alert-msg" aria-hidden="true"><i
                                    class="fa fa-times" aria-hidden="true"></i> :message</span>') !!}
                        </div>
                    </div>

                    <!-- Expected Checkin Date -->
                    <div class="form-group {{ $errors->has('expected_checkin') ? 'error' : '' }}">
                        {{ Form::label('expected_checkin', trans('admin/hardware/form.expected_checkin'), array('class' => 'col-md-3 control-label')) }}
                        <div class="col-md-8">
                            <div class="input-group date col-md-5" data-provide="datepicker"
                                data-date-format="yyyy-mm-dd" data-date-start-date="0d">
                                <input type="text" class="form-control" placeholder="{{ trans('general.select_date') }}"
                                    name="expected_checkin" id="expected_checkin" value="{{ old('expected_checkin') }}">
                                <span class="input-group-addon"><i class="fa fa-calendar" aria-hidden="true"></i></span>
                            </div>
                            {!! $errors->first('expected_checkin', '<span class="alert-msg" aria-hidden="true"><i
                                    class="fa fa-times" aria-hidden="true"></i> :message</span>') !!}
                        </div>
                    </div>


                    <!-- Notes -->
                    <div class="form-group {{ $errors->has('notes') ? ' has-error' : '' }}">
                        <label for="notes"
                            class="col-md-3 control-label">{{ trans('admin/hardware/form.notes') }}</label>
                        <div class="col-md-7">
                            <textarea class="col-md-6 form-control" id="notes"
                                name="notes">{{ old('notes') }}</textarea>
                            {!! $errors->first('notes', '<span class="alert-msg" aria-hidden="true"><i
                                    class="fa fa-times" aria-hidden="true"></i> :message</span>') !!}
                        </div>
                    </div>
            </div>
            <!--./box-body-->
            <div class="box-footer">
                <a class="btn btn-link" href="{{ URL::previous() }}"> {{ trans('button.cancel') }}</a>
                <button type="submit" class="btn btn-primary pull-right"><i class="fa fa-check icon-white"
                        aria-hidden="true"></i> {{ trans('general.checkout') }}</button>
            </div>
        </div>
        </form>
    </div>
    <!--/.col-md-7-->

    <!-- right column -->
    <div class="col-md-5" id="current_assets_box" style="display:none;">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h2 class="box-title">{{ trans('admin/users/general.current_assets') }}</h2>
            </div>
            <div class="box-body">
                <div id="current_assets_content">
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('moar_scripts')
@include('partials/assets-assigned')

@stop
