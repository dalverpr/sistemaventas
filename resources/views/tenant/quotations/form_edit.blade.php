@extends('tenant.layouts.app')

@section('content')
    <tenant-quotations-edit 
        :resource-id="{{json_encode($resourceId)}}" 
        :type-user="{{json_encode(Auth::user()->type)}}"
        :configuration="{{\App\Models\Tenant\Configuration::getPublicConfig()}}"
        :auth-user="{{json_encode(Auth::user()->getDataOnlyAuthUser())}}"
    >
    </tenant-quotations-edit>
@endsection
