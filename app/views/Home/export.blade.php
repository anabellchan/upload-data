@extends('layouts.basic')

@section('headers')
    <style>

    </style>
@stop


@section('maincontent')
	<div>
        <h1>Export Data</h1>
        <br/>
        <br/>
        {{ Form::open(['url'=>'export/category']) }}
        {{ Form::label('categories','Select Category for Uploaded Items',['id'=>'','class'=>'']) }}
        <select id="categories" name="categories">
            @forelse($categories as $cat)
                <option value="{{$cat}}">{{$cat}}</option>
            @empty
                <option value="none">ERROR!</option>
            @endforelse
        </select>
        <!-- buttons -->
        {{ Form::submit('Export') }}

        {{ Form::close() }}
	</div>
    <div>
        <br>
        <a href="{{ URL::to('/') }}">Back</a>
    </div>

@stop
