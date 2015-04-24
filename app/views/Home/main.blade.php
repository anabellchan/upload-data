@extends('layouts.basic')

@section('headers')
    <style>

    </style>
@stop


@section('maincontent')
<div><a href='https://daq03.triumf.ca/daqinv/frontend/'>Return to LADD/DAQ Inventory System</a></div>
	<div>
	<br>
        <h1>Import/Export Data Application</h1>
		{{ HTML::image('upload.png', 'Upload an Excel spreadsheet to database.') }}
        <br/>
        <br/>
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
	
	
@stop
