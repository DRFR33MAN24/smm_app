@extends('admin.layouts.app')
@section('title')
    @lang('Send Notification')
@endsection
@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-primary m-0 m-md-4 my-4 m-md-0 shadow">
                <div class="card-body">
                    <form method="post" action="{{ route('admin.notify-template.store') }}" class="needs-validation base-form" enctype="multipart/form-data">
                        @csrf
                        <div class="column ">
                            <div class="form-group  ">
                                <label class="control-label">@lang('Title')</label>
                                <input type="text" name="title" value="{{ old('title',env('title')) }}"
                                       required="required" class="form-control ">
                                @error('title')
                                <span class="text-danger">{{ trans($message) }}</span>
                                @enderror
                            </div>
                            <div class="form-group mt-4">
                    <label class="control-label " for="fieldone">@lang('Description')</label>
                    <textarea class="form-control" rows="4" placeholder="@lang('Description') " name="description"></textarea>
                    @if($errors->has('description'))
                        <div class="error text-danger">@lang($errors->first('description')) </div>
                    @endif
                </div>





                            <div class="form-group">
                            <div class="image-input ">
                                <label for="image-upload" id="image-label"><i class="fas fa-upload"></i></label>
                                <input type="file" name="image" placeholder="Choose image" id="image">
                                <img id="image_preview_container" class="preview-image" src="{{ getFile(config('location.default')) }}"
                                     alt="preview image">
                            </div>

                            @error('image')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>


                        </div>
                        <button type="submit" class="btn waves-effect waves-light btn-rounded btn-primary btn-block mt-3"><span><i
                                    class="fas fa-save pr-2"></i> @lang('Send')</span></button>
                    </form>
                </div>
            </div>
        </div>
 
    </div>

    @push('js')
    <script>
            "use strict";



            $(document).ready(function () {


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
@endsection

