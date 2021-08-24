@extends('layouts.app')

@section('content')
<div class="container">
    <br>
    <div class="row justify-content-center">
        <div class="col-md-12">
            <h2>{{ $user->name }}'s History</h2>
        </div>
        <br>
        <div class="col-md-12">
            @if (session('success'))
            <div class="alert alert-success" role="alert">
                {{ session('success') }}
            </div>
            @endif
            @if (session('error'))
            <div class="alert alert-danger" role="alert">
                {{ session('error') }}
            </div>
            @endif
            <table class="table table-bordered">
                <thead class="thead-light">
                    <tr>
                        <th>Date</th>
                        <th>Master</th>
                        <th>Bet time</th>
                        <th>Amount</th>
                        <th>Type</th>
                        <th>Result</th>
                        <th>Gain</th>
                        <th>Account Type</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($histories as $history)
                    <tr>
                        <td>{{ $history->created_at }}</td>
                        <td>{!! $history->master !!}</td>
                        <td>{{ $history->bet_secs }}s</td>
                        <td>${{ $history->amount }}</td>
                        <td>{{ $history->type === 0 ? "Buy" : "Sell" }}</td>
                        <td>{{ $history->result }}</td>
                        <td>${{ $history->gain }}</td>
                        <td>{{ $history->is_demo ? "DEMO" : "REAL" }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4">
                            <center>No data found</center>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <br>
        <div class="col-md-12 d-flex justify-content-center">
			{{ $histories->links() }}
		</div>
    </div>
</div>

@endsection
