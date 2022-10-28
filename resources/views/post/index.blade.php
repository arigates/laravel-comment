@extends('layout')

@section('content')
    <div class="row" style="margin-top: 10px">
        <div class="col">
            <a class="btn btn-danger float-right ml-1" href="{{ route('logout') }}">Logout</a>
            @if($user->role === 'admin')
            <a class="btn btn-primary float-right" href="{{ route('post.create') }}">Buat Post</a>
            @endif
        </div>
    </div>
    <div class="row" style="margin-top: 10px">
        <div class="col-12">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Judul</th>
                        <th>Deskripsi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($posts as $key => $post)
                        <tr>
                            <td>{{ $key + 1 }}</td>
                            <td><a href="{{ route('post.show', ['slug' => $post->slug]) }}">{{ $post->title }}</a></td>
                            <td>{{ $post->description }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
