@extends('layouts.basic')

@section('headers')
    <style>

    </style>
@stop


@section('maincontent')
<div><a href='https://daq03.triumf.ca/daqinv/frontend/'>Return to LADD/DAQ Inventory System</a></div>
	<div>
	<br>
        <h1>Export Data</h1>
		{{ HTML::image('upload.png', 'Upload an Excel spreadsheet to database.') }}
        <br/>
        <br/>
        {{ Form::open(['url'=>'export/category']) }}
        {{ Form::label('categories','Select Category for Uploaded Items',['id'=>'','class'=>'']) }}
        <select id="categories" name="categories">
            {{$categories}}
        </select>
        <!-- buttons -->
        {{ Form::submit('Export') }}

        {{ Form::close() }}
	</div>
    <div>
        <br>
		<br>
        <a href="{{ URL::to('/') }}">Return to Import/Export Home</a>
    </div>

@stop
