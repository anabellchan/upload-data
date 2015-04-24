@extends('layouts.basic')

@section('headers')
    <style>

    </style>
@stop


@section('maincontent')
<div><a href='https://daq03.triumf.ca/daqinv/frontend/'>Return to LADD/DAQ Inventory System</a></div>
	<div>
		<br>
        <h1>Import Data</h1>
        <!-- upload image -->
        {{ HTML::image('upload.png', 'Upload an Excel spreadsheet to database.') }}
        <br/>
        <br/>
        <a href="{{ URL::to('https://daq03.triumf.ca/daqinv/config/categories') }}">Create a Category</a><br/>
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
		<br>
        <a href="{{ URL::to('/') }}">Return to Import/Export Home</a>
    </div>

@stop
