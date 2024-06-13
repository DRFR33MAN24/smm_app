@extends('admin.layouts.app')
@section('title')
    @lang('Card Show')
@endsection

@section('content')

    <div class="page-header card card-primary m-0 m-md-4 my-4 m-md-0 p-5">
        <div class="row">
            <div class="col-xl-10">
                <form action="{{route('admin.card-search')}}" method="get">
                    <div class="row">
                        <div class="col-md-4 col-xl-3">
                            <div class="form-group">
                                <input type="text" name="token" value="{{@request()->token}}"
                                       class="form-control"
                                       placeholder="@lang('Type Here')">
                            </div>
                        </div>

                        <div class="col-md-4 col-xl-3">
                            <div class="form-group">
                                <select name="status" class="form-control" id="status">
                                    <option value="" >@lang('All Status')</option>
                                    <option value="1"
                                            @if(@request()->status == '1') selected @endif>@lang('Active')</option>
                                    <option value="0"
                                            @if(@request()->status == '0') selected @endif>@lang('Inactive')</option>
                                </select>
                            </div>
                        </div>


                        <div class="col-md-4 col-xl-3">
                            <div class="form-group">
                                <button type="submit" class="btn w-100 w-sm-auto btn-primary"><i
                                        class="fas fa-search"></i> @lang('Search')</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="col-xl-2">
                <div class="d-flex justify-content-start justify-content-xl-end">

                    <a href="{{route('admin.card.add')}}"
                       class="btn btn-primary btn-sm mr-3"><span>@lang('Add Card')</span></a>

                    <div class="dropdown">
                        <button class="btn btn-sm btn-dark dropdown-toggle" type="button" id="dropdownMenuButton"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span><i class="fas fa-bars pr-2"></i> @lang('Action')</span>
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <button class="dropdown-item" type="button" data-toggle="modal"
                                    data-target="#all_active">@lang('Active')</button>
                            <button class="dropdown-item" type="button" data-toggle="modal"
                                    data-target="#all_deactive">@lang('Inactive')</button>
                        </div>
                    </div>
                </div>


            </div>
        </div>
    </div>

    <div class="card card-primary m-0 m-md-4 my-4 m-md-0">

        <div class="card-body">
            <div class="table-responsive">
                <table class="categories-show-table table table-hover table-striped table-bordered">
                    <thead class="thead-primary">
                    <tr>
                        <th scope="col" class="text-center">
                            <input type="checkbox" class="form-check-input check-all tic-check" name="check-all"
                                   id="check-all">
                            <label for="check-all"></label>
                        </th>
                     
                      <th scope="col">@lang('Serial')</th>
                        <th scope="col">@lang('Token')</th>
                        <th scope="col">@lang('Exp')</th>
                        <th scope="col">@lang('Status')</th>
                        <th scope="col">@lang('Action')</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($cards as $card)
                        <tr>
                            <td class="text-center">
                                <input type="checkbox" id="chk-{{$card->id}}"
                                       class="form-check-input row-tic tic-check"
                                       name="check[]" value="{{ $card->id }}" data-id="{{ $card->id }}">
                                <label for="chk-{{$card->id}}"></label>
                            </td>
                                <td data-label="@lang('Token')">
                                {!! $card->serial ?? 'N/A' !!}
                            </td>

                            <td data-label="@lang('Token')">
                                {!! $card->token ?? 'N/A' !!}
                            </td>
                                                        <td data-label="@lang('EXP')">
                                {!! $card->expiry ?? 'N/A' !!}
                            </td>
                            <td data-label="@lang('Status')">
                                <span
                                    class="badge badge-pill {{ $card->status == 0 ? 'badge-danger' : 'badge-success' }}">{{ $card->status == 0 ? 'Inactive' : 'Active' }}</span>
                            </td>
                            <td data-label="@lang('Action')">




                                <div class="dropdown show dropup">
                                    <a class="dropdown-toggle" href="#" id="dropdownMenuLink" data-toggle="dropdown"
                                       aria-haspopup="true" aria-expanded="false">
                                        <i class="fa fa-ellipsis-v" aria-hidden="true"></i>
                                    </a>
                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuLink">
                                        <a  class="dropdown-item"
                                            href="{{route('admin.card.edit',['id'=>$card->id])}}">
                                            <i class="fa fa-edit text-warning pr-2"
                                               aria-hidden="true"></i> @lang('Edit')</a>

                                        <a href="javascript:void(0)"
                                           class="dropdown-item status-change {{ $card->status == 0 ? 'text-success' : 'text-danger' }}"
                                           data-toggle="modal"
                                           data-target="#statusMoldal"
                                           data-route="{{route('admin.card.status.change',['id'=>$card->id])}}">
                                            <i class="fa fa-check "
                                               aria-hidden="true"></i>
                                            {{ $card->status == 0 ? 'Activate' : 'Deactivate' }}
                                        </a>
                                    </div>
                                </div>

                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="statusMoldal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog " role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">@lang('Confirm Status Change')</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="" method="post" id="statusForm">
                    @csrf
                    <div class="modal-body">
                        <p>@lang('Are you really want to change the current status of the card')</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal"><span> @lang('Cancel')</span></button>
                        <button type="submit" class="btn btn-primary"><span> @lang('Change Status')</span> </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="all_active" role="dialog">
        <div class="modal-dialog ">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">@lang('Confirm to active status')</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                        <p>@lang('Are you really want to active the card')</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal"><span>@lang('No')</span></button>
                    <button type="button" class="btn btn-primary active-yes"><span>@lang('Yes')</span></button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="all_deactive" role="dialog">
        <div class="modal-dialog ">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">@lang('Confirm to deactive status')</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <p>@lang('Are you really want to deactive the card')</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal"><span>@lang('No')</span>
                        <button type="button" class="btn btn-primary deactive-yes"><span>@lang('Yes')</span></button>
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection


@push('js')
    <script>
        "use strict";
        $(document).ready(function () {

            $(document).on('click', '#check-all', function () {
                $('input:checkbox').not(this).prop('checked', this.checked);
            });

            $(document).on('change', ".row-tic", function () {
                let length = $(".row-tic").length;
                let checkedLength = $(".row-tic:checked").length;
                if (length == checkedLength) {
                    $('#check-all').prop('checked', true);
                } else {
                    $('#check-all').prop('checked', false);
                }
            });

            $(document).on('click', '.status-change', function () {
                let route = $(this).data('route');
                $('#statusForm').attr('action', route);
            });

            $(document).on('click', '.dropdown-menu', function (e) {
                e.stopPropagation();
            });


            //multiple active
            $(document).on('click', '.active-yes', function (e) {
                e.preventDefault();
                var allVals = [];
                $(".row-tic:checked").each(function () {
                    allVals.push($(this).attr('data-id'));
                });
                if (allVals.length > 0) {
                    var strIds = allVals.join(",");
                    $.ajax({
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf_token"]').attr('content')},
                        url: "{{ route('admin.card-multiple-active') }}",
                        data: {strIds: strIds},
                        datatType: 'json',
                        type: "get",
                        success: function (data) {
                            if (data.success == 1) {
                                location.reload();
                            }
                        }
                    });
                } else {
                    var strIds = allVals.join(",");
                    $.ajax({
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf_token"]').attr('content')},
                        url: "{{ route('admin.card-multiple-active') }}",
                        data: {strIds: strIds},
                        datatType: 'json',
                        type: "get",
                        success: function (data) {
                            if (data.error == 1) {
                                location.reload();
                            }
                        }
                    });
                }
            });
            //multiple deactive
            $(document).on('click', '.deactive-yes', function (e) {
                e.preventDefault();
                var allVals = [];
                $(".row-tic:checked").each(function () {
                    allVals.push($(this).attr('data-id'));
                });
                if (allVals.length > 0) {
                    var strIds = allVals.join(",");
                    $.ajax({
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf_token"]').attr('content')},
                        url: "{{ route('admin.card-multiple-deactive') }}",
                        data: {strIds: strIds},
                        datatType: 'json',
                        type: "get",
                        success: function (data) {
                            if (data.success == 1) {
                                location.reload();
                            }
                        }
                    });
                } else {
                    var strIds = allVals.join(",");
                    $.ajax({
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf_token"]').attr('content')},
                        url: "{{ route('admin.card-multiple-deactive') }}",
                        data: {strIds: strIds},
                        datatType: 'json',
                        type: "get",
                        success: function (data) {
                            if (data.error == 1) {
                                location.reload();
                            }
                        }
                    });
                }
            });

        });

    </script>

    <script>
            "use strict";
            $(document).ready(function () {

                $('#status').select2({
                    selectOnClose: true
            });
            });
    </script>
@endpush
