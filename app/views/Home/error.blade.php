@extends('layouts.basic')

@section('headers')
    <style>

    </style>
@stop


@section('maincontent')

    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center">
                <h2 class="section-heading">The following errors must be corrected:</h2>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12 text-center">
                  <p>{{$message}}</p>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12 text-center">
                <a href="/">Back</a>
            </div>
        </div>
    </div>
@stop
