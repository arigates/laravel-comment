@extends('layout')

@section('content')
    <div class="row">
        <div class="card card-post">
            <div class="card-header">
                <h2>Apa yang ingin Anda bagikan?</h2>
            </div>
            <div class="card-body">
                <form action="{{ route('post.store') }}" method="POST" enctype="multipart/form-data" id="create-post">
                    @csrf
                    <div class="form-group">
                        <input class="form-control" name="title" placeholder="Tulis judul disini" required>
                    </div>
                    <div class="form-group">
                        <textarea class="form-control" name="description" placeholder="Tulis deskripsi disini" required></textarea>
                    </div>
                    <div class="form-group">
                        <input type="file" multiple name="media[]" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-primary submit-button">Bagikan</button>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        $('#create-post').submit(function (e) {
            e.preventDefault();
            let formData = new FormData(this);
            let url = this.action;

            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                enctype: 'multipart/form-data',
            }).done(function (data) {
                let redirect = '{{ route('post.show', ['slug' => ':slug']) }}'
                redirect = redirect.replace(':slug', data.slug)
                window.location.href = redirect
            });
        })
    </script>
@endpush
