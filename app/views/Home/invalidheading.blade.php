@extends('layouts.basic')

@section('headers')
	<style>

	</style>
@stop


@section('maincontent')

    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center">
                <h2 class="section-heading">Invalid SpreadSheet Heading(s)</h2>
            </div>
        </div>

        @if($allItems['itemName']== false)
            <div class="row">
                <div class="col-lg-12 text-center">
                    <h3 class="section-heading">Required Column Heading(s) - Must Add to Spreadsheet</h3>
                        <li>
                            item_name
                        </li>
                </div>
            </div>
        @endif

        @if(count($allItems['invalidHeaders']) > 0)
            <div class="row">
                <div class="col-lg-12 text-center">
                    <h3 class="section-heading">List of Invalid Headings from Import SpreadSheet</h3>
                    @forelse($allItems['invalidHeaders'] as $invalid)
                        <li>
                            {{$invalid}}
                        </li>
                    @empty
                        <p>No Invalid Headers - IN THAT CASE THIS SHOULD NOT BE DISPLAYED!</p>
                    @endforelse
                </div>
            </div>
            <div class="row">
                <div class="col-lg-12 text-center">
                    <h3 class="section-heading">List of Valid Heading Alternatives</h3>
                    @forelse($allItems['validHeaders'] as $valid)
                        <li>
                            {{$valid}}
                        </li>
                    @empty
                        <p>No Valid Headers - THIS IS IMPOSSIBLE!</p>
                    @endforelse
                </div>
            </div>
        @endif

    </div>
@stop
