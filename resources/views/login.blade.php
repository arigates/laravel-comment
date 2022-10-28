@extends('layout')

@section('content')
    <div class="card card-login">
        <div class="card-body">
            @if($errors->has('message'))
                <p class="text-center text-danger"> {{ $errors->first('message') }} </p>
            @endif
            <form action="{{ route('login.submit') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" name="email" required class="form-control" id="email">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" required class="form-control" id="password">
                </div>

                <div class="form-group">
                    <button class="btn btn-primary w-100">Login</button>
                </div>
            </form>
        </div>
    </div>
@endsection
