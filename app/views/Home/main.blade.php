@extends('layouts.basic')

@section('headers')
    <style>

    </style>
@stop


@section('maincontent')
	<div>
        <h1>Upload Data</h1>
    </div>
    <div>
        <br>
        <a href="{{URL::to('import')}}">Import Data</a>
    </div>
    <div>
        <br>
        <a href="{{ URL::to('export') }}">Export Data</a>
    </div>
    <div>
        <br>
        <a href="{{ URL::to('template') }}">Download Inventory Spreadsheet Template</a>
    </div>
	
	
	
	<div>
	<br>
	token test - url token is {{$alltokens['urltoken']}}
	@forelse($alltokens['tokens'] as $t)
		<li>{{$t}}</li>
	@empty
		<li>no tokens found</li>
	@endforelse
		</div>
	
	
@stop
