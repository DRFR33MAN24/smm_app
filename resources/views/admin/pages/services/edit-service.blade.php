@extends('admin.layouts.app')
@section('title')
    @lang('Edit Service')
@endsection
@section('content')
    <div class="card card-primary card-form m-0 m-md-4 my-4 m-md-0 shadow">
        <div class="card-body ">
            {{--@dd($errors)--}}
            <form action="{{route('admin.service.update')}}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="col-sm-6 col-md-3">
                        <div class="form-group">
                            <div class="image-input ">
                                <label for="image-upload" id="image-label"><i class="fas fa-upload"></i></label>
                                <input type="file" name="image" placeholder="Choose image" id="image">
                                <img src="{{ getFile(config('location.service.path').$service->image)}}" alt="service thum" id="image_preview_container" class="img-fluid preview-image">


                            </div>

                            @error('image')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                <h5 class="table-group-title text-info mb-2 mb-md-3"><span>@lang('Service Basic')</span></h5>
                <input type="hidden" name="id" value="{{$service->id}}">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group ">
                            <label>@lang('Service Title')</label>
                            <input type="text" name="service_title"
                                   value="{{old('service_title',$service->service_title)}}"
                                   class="form-control form-control-sm">
                            @if($errors->has('service_title'))
                                <div class="error text-danger">@lang($errors->first('service_title')) </div>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>@lang('Select Category')</label>
                            <select class="form-control" id="category_id" name="category_id">
                                <option value="{{old('category_id',$service->category_id)}}" selected
                                        hidden>@lang('Change Category')</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id  }}"
                                            @if($service->category_id == $category->id ) selected @endif>{{ $category->category_title  }}</option>
                                @endforeach
                            </select>

                        </div>
                        @if($errors->has('category_id'))
                            <div class="error text-danger mt-2">@lang($errors->first('category_id')) </div>
                        @endif
                    </div>
                </div>
                <div class="divider"></div>
                <h5 class="table-group-title text-primary mb-2 mb-md-3"><span>@lang('Price & Status')</span></h5>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>@lang('Minimum Amount')</label>
                            <input type="number" class="form-control square" name="min_amount"
                                   value="{{old('min_amount',$service->min_amount)}}">
                            @if($errors->has('min_amount'))
                                <div class="error text-danger">@lang($errors->first('min_amount')) </div>
                            @endif
                        </div>
                        <div class="form-group">
                            <label>@lang('Price') </label>
                            <input type="text" class="form-control square" name="price" placeholder="50.25"
                                   value="{{old('price',$service->price)}}">
                            @if($errors->has('price'))
                                <div class="error text-danger">@lang($errors->first('price')) </div>
                            @endif
                        </div>
                        <div class="form-group">
                            <label>@lang('Reseller Price') </label>
                            <input type="text" class="form-control square" name="reseller_price" placeholder="50.25"
                                   value="{{old('reseller_price',$service->reseller_price)}}">
                            @if($errors->has('reseller_price'))
                                <div class="error text-danger">@lang($errors->first('reseller_price')) </div>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>@lang('Maximum Amount')</label>
                            <input type="number" class="form-control square" name="max_amount"
                                   value="{{old('max_amount',$service->max_amount)}}">
                            @if($errors->has('max_amount'))
                                <div class="error text-danger">@lang($errors->first('max_amount')) </div>
                            @endif
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group ">
                                    <label class="d-block">@lang('Status') </label>
                                    <div class="custom-switch-btn">
                                        <input type='hidden' value='1' name='service_status'>
                                        <input type="checkbox" name="service_status" class="custom-switch-checkbox"
                                               id="service_status"
                                               value="0" {{ $service->service_status == '0' ? 'checked': '' }} >
                                        <label class="custom-switch-checkbox-label" for="service_status">
                                            <span class="custom-switch-checkbox-inner"></span>
                                            <span class="custom-switch-checkbox-switch"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
      
                        </div>
                    </div>
                </div>
                <div class="divider"></div>
                <h5 class="table-group-title text-primary mb-2 mb-md-3"><span>@lang('Type & Details') </span></h5>
                <div class="form-group ">
                    <div class="switch-field d-flex">
                        <div class="form-check p-0">
                            <input class="form-check-input" type="radio" name="manual_api" id="less"
                                   value="0" {{ old('manual_api') == 0 || !isset($service->api_provider_id) || !isset($service->api_service_id) ? 'checked' : '' }}>
                            <label class="form-check-label" for="less">
                                @lang('No Sync')
                            </label>
                        </div>
                        <div class="form-check p-0">
                            <input class="form-check-input" type="radio" name="manual_api" id="more"
                                   value="1" {{ old('manual_api') == 1 || isset($service->api_provider_id, $service->api_service_id) ? 'checked' : '' }}>
                            <label class="form-check-label" for="more">
                                @lang('Sync with Provider')
                            </label>
                        </div>
                    </div>
                </div>
                <div class="row moreField {{ old('manual_api') == 1 || isset($service->api_provider_id, $service->api_service_id) ? '' : 'd-none'  }}">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="control-label " for="apiprovider">@lang('API Provider Name')</label>
                            <select class="form-control" name="api_provider_id">
                                <option value="0">@lang('Select API Provider name')</option>
                                @foreach($apiProviders as $apiProvider)
                                    <option value="{{ $apiProvider->id }}" {{ old('api_provider_id', $service->api_provider_id) == $apiProvider->id ? 'selected' : '' }}>{{ $apiProvider->api_name  }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('api_provider_id'))
                                <div class="error text-danger">@lang($errors->first('api_provider_id')) </div>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>@lang('API Service ID')</label>
                            <input type="text" class="form-control square" name="api_service_id"
                                   value="{{old('api_service_id', $service->api_service_id)}}"
                                   placeholder="@lang('Api Service ID')">
                            @if($errors->has('api_service_id'))
                                <div class="error text-danger">@lang($errors->first('api_service_id')) </div>
                            @endif
                        </div>
                    </div>
                </div>

   

                <div class="form-group mt-4">
                    <label class="control-label " for="description">@lang('Description')</label>
                    <textarea class="form-control" rows="8"
                              name="description">{{ old('description', $service->description) }}</textarea>

                </div>
                                <div class="form-group mt-4">
                    <label class="control-label " for="custom_fields">@lang('Custom Fields')</label>
                    <textarea class="form-control" rows="8"
                              name="custom_fields">{{ old('custom_fields', $service->custom_fields) }}</textarea>

                </div>
                <button type="submit" class="btn btn-primary btn-block mt-3"><span><i
                                class="fas fa-save pr-2"></i> @lang('Save Changes')</span>
                </button>
            </form>
        </div>
    </div>
@endsection

@push('js')
    <script>
        "use strict";

        var $serviceType = '';

        $serviceType = $('input[name=manual_api]:checked').val();

        checkType($serviceType);

        $(document).on('click', '#more', function () {
            $(".moreField").removeClass('d-none');

        });
        $(document).on('click', '#less', function () {
            $(".moreField").addClass('d-none');
        });

        $(document).on('click',"input[name=manual_api]:checked", function () {
        $serviceType = $(this).val();
        checkType($serviceType);
        });

        function checkType(serviceType){
            if(serviceType == 0){
                // $('select[name=refill]').val('')
                $(".automatic").addClass('d-none');
                return 0;
            }else{
                // $('select[name=refill]').val('')
                $(".automatic").removeClass('d-none');
                return 0;
            }

        }

        $(document).ready(function () {
            $('#category_id').select2({
                selectOnClose: true
            });

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
            });

            $('#image').on('change',function(){
                let reader = new FileReader();
                reader.onload = (e) => {
                    $('#image_preview_container').attr('src', e.target.result);
                }
                reader.readAsDataURL(this.files[0]);
            });

            $('#upload_image_form').on('submit',function(e) {
                e.preventDefault();
                var formData = new FormData(this);
                $.ajax({
                    type:'POST',
                    url: "{{ url('photo')}}",
                    data: formData,
                    cache:false,
                    contentType: false,
                    processData: false,
                    success: (data) => {
                    this.reset();
                    alert('Image has been uploaded successfully');
                    },
                    error: function(data){
                    console.log(data);
                    }
                });
            });
        });
    </script>
@endpush
