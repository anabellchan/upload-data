@extends('layouts.basic')

@section('headers')
	<style>

	</style>
@stop


@section('maincontent')
    <div class="container">
        @if(count($allItems['invalidRows']) > 0)
            <h1>Errors found:</h1>
            <div class="row">
                 <div class="col-lg-12 text-center">
                    <h3>{{{$allItems['validation']}}}</h3>
                    @forelse($allItems['invalidRows'] as $invalid)
                        <li>
                            {{$invalid}}
                        </li>
                    @empty
                        <p>No Invalid Headers - IN THAT CASE THIS SHOULD NOT BE DISPLAYED!</p>
                    @endforelse
                </div>
            </div>
        @endif
        <div>
            <br>
            <a href="{{ URL::previous() }}">Back</a>
        </div>

    </div>
@stop
