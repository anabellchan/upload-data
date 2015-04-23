@extends('layouts.basic')

@section('headers')
    <style>

    </style>
@stop


@section('maincontent')
	<div>
        <h1>Import Data</h1>
        <!-- upload image -->
        {{ HTML::image('upload.png', 'Upload an Excel spreadsheet to database.') }}
        <br/>
        <br/>
        {{ Form::open(['url'=>'import/file','files'=>true]) }}
        {{ Form::label('categories','Select Category for Uploaded Items',['id'=>'','class'=>'']) }}
        <!--{{ Form::select('categories', array('L' => 'Large', 'S' => 'Small')) }}-->
        {{--<select id="categories" name="categories">--}}
            {{--<option value=""> </option>--}}
            {{--@forelse($categories as $cat)--}}
                {{--<option value="{{$cat}}">{{$cat}}</option>--}}
            {{--@empty--}}
                {{--<option value="none">ERROR!</option>--}}
            {{--@endforelse--}}
        {{--</select>--}}
        {{$selectionOfCategories}}

        <br/>
        <br/>

        {{ Form::label('file','File',['id'=>'','class'=>'']) }}
        {{ Form::file('file','',['id'=>'','class'=>'']) }}
        <br/>
        <br/>

        <!-- buttons -->
        {{ Form::submit('Import') }}
        {{ Form::reset('Reset') }}

        {{ Form::close() }}
	</div>
    <div>
        <br>
        <a href="{{ URL::to('/') }}">Back</a>
    </div>

@stop
