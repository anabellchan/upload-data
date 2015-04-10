@extends('layouts.basic')

@section('headers')
    <style>

    </style>
@stop


@section('maincontent')
	<div class="welcome">
        <h1>Upload Data</h1>
        <!-- upload image -->
        {{ HTML::image('upload.png', 'Upload an Excel spreadsheet to database.') }}

        {{ Form::open(['url'=>'upload/file','files'=>true]) }}
        {{ Form::label('file','File',['id'=>'','class'=>'']) }}
        {{ Form::file('file','',['id'=>'','class'=>'']) }}
        <br/>
        <!-- buttons -->
        {{ Form::submit('Save') }}
        {{ Form::reset('Reset') }}

        {{ Form::close() }}

	</div>
@stop
