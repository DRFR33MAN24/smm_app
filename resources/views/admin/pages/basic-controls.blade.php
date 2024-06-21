@extends('admin.layouts.app')
@section('title')
    @lang('Basic Controls')
@endsection
@section('content')

    <div class="alert alert-warning my-5 m-0 m-md-4" role="alert">
        <i class="fas fa-info-circle mr-2"></i> @lang("If you get 500(server error) for some reason, please turn on <b>Error Log</b> and try again. Then you can see what was missing in your system.")
    </div>
    <div class="card card-primary m-0 m-md-4 my-4 m-md-0">
        <div class="card-body">
            <form method="post" action="" novalidate="novalidate"
                  class="needs-validation base-form">
                @csrf
                <div class="row">

                    <div class="form-group col-md-6">
                        <label class="font-weight-bold">@lang('Company Phone')</label>
                        <input type="text" name="company_phone"
                               value="{{ old('company_phone') ?? $settings['company_phone'] ?? 'Company Phone' }}"
                               class="form-control ">

                        @error('company_phone')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror

                    </div>
                    
                                        <div class="form-group col-md-6">
                        <label class="font-weight-bold">@lang('Company Phone 2')</label>
                        <input type="text" name="company_phone2"
                               value="{{ old('company_phone2') ?? $settings['company_phone2'] ?? 'Company Phone 2' }}"
                               class="form-control ">

                        @error('company_phone2')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror

                    </div>
                                        <div class="form-group col-md-6">
                        <label class="font-weight-bold">@lang('USDT Wallet')</label>
                        <input type="text" name="usdt_wallet"
                               value="{{ old('usdt_wallet') ?? $settings['usdt_wallet'] ?? 'USDT Wallet' }}"
                               class="form-control ">

                        @error('usdt_wallet')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror

                    </div>
                    <div class="form-group col-md-6">
                        <label class="font-weight-bold">@lang('Company Email')</label>
                        <input type="text" name="company_email"
                               value="{{ old('company_email') ?? $settings['company_email'] ?? 'Company Email' }}"
                               class="form-control ">

                        @error('company_email')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror

                    </div>
                    <div class="form-group col-md-6">
                        <label class="font-weight-bold">@lang('Currency Conversion Factor')</label>
                        <input type="text" name="currency_conversion_factor"
                               value="{{ old('currency_conversion_factor') ?? $settings['currency_conversion_factor'] ?? 'Currency Conversion Factor' }}"
                               class="form-control ">

                        @error('currency_conversion_factor')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror

                    </div>

                    <div class="form-group col-md-6">
                        <label class="font-weight-bold">@lang('Credit Transfer Percent')</label>
                        <input type="text" name="credit_transfer_percent"
                               value="{{ old('credit_transfer_percent') ?? $settings['credit_transfer_percent'] ?? 'Credit Transfer Percent' }}"
                               class="form-control ">

                        @error('credit_transfer_percent')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror

                    </div>
                    <div class="form-group col-md-6">
                        <label class="font-weight-bold">@lang('Ticker Text')</label>
                        <input type="text" name="ticker_text"
                               value="{{ old('ticker_text') ?? $settings['ticker_text'] ?? 'Ticker Text' }}"
                               class="form-control ">

                        @error('ticker_text')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror

                    </div>
                    <div class="form-group col-md-6">
                        <label class="font-weight-bold">@lang('FCM key')</label>
                        <input type="text" name="push_notification_key"
                               value="{{ old('push_notification_key') ?? $settings['push_notification_key'] ?? 'FCM Key' }}"
                               class="form-control ">

                        @error('push_notification_key')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror

                    </div>
                    <div class="form-group col-md-6">
                                    <label class="d-block">@lang('Maintenance')</label>
                                    <div class="custom-switch-btn">
                                        <input type='hidden' value='1' name='ment'>
                                        <input type="checkbox" name="ment" class="custom-switch-checkbox"
                                               id="ment" value= "0" {{ $settings['maintenance_mode'] == '0' ? 'checked' : '' }}>
                                        <label class="custom-switch-checkbox-label" for="ment">
                                            <span class="custom-switch-checkbox-inner"></span>
                                            <span class="custom-switch-checkbox-switch"></span>
                                        </label>
                                    </div>
                                </div>

     

                  
                                                                    <div class="form-group col-md-12">
                                                                        <label
                                                                            for="policy">Privacy Policy</label>
                                                                        <textarea
                                                                        id="summernote"
                                                                            class="form-control  summernote "
                                                                            name="policy"
                                                                            rows="15"><?php echo $settings['privacy_policy'] ?></textarea>
                                                                        <div class="invalid-feedback">
                                                                          
                                                                        </div>
                                                                    </div>
                                                                    <div class="form-group col-md-12">
                                                                        <label
                                                                            for="terms">Terms and Conditions</label>
                                                                        <textarea
                                                                        id="terms"
                                                                            class="form-control  summernote "
                                                                            name="terms"
                                                                            rows="15"><?php echo $settings['terms_and_conditions'] ?></textarea>
                                                                        <div class="invalid-feedback">
                                                                          
                                                                        </div>
                                                                    </div>
                                                           



                </div>
                <button type="submit" class="btn waves-effect waves-light btn-rounded btn-primary btn-block mt-3"><span><i
                            class="fas fa-save pr-2"></i> @lang('Save Changes')</span></button>
            </form>
        </div>
    </div>
@endsection

@push('style-lib')
    <link rel="stylesheet" href="{{ asset('assets/admin/css/summernote.min.css')}}">
@endpush
@push('js-lib')
    <script src="{{ asset('assets/global/js/summernote.min.js')}}"></script>
@endpush
@push('js')
    <script>
        $(document).ready(function () {
            "use strict";
            $('select[name=time_zone]').select2({
                selectOnClose: true
            });

 

            $('#summernote').summernote({
                callbacks: {
                    onBlurCodeview: function () {
                        let codeviewHtml = $(this).siblings('div.note-editor').find('.note-codable').val();
                        $(this).val(codeviewHtml);
                    }
                }
            });
            $('#terms').summernote({
                callbacks: {
                    onBlurCodeview: function () {
                        let codeviewHtml = $(this).siblings('div.note-editor').find('.note-codable').val();
                        $(this).val(codeviewHtml);
                    }
                }
            });
        });
    
    </script>
@endpush

