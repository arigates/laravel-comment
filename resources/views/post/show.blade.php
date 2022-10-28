@extends('layout')

@section('content')
    <div class="row" data-post-url="{{ route('post.comment', ['post' => $post->id]) }}" id="post">
        <div class="card card-post">
            <div class="card-header">
                <div class="dropdown">
                    <button class="btn btn-primary dropdown-toggle float-right" type="button" data-toggle="dropdown" aria-expanded="false">
                        Aksi
                    </button>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="#">Edit</a>
                        <a class="dropdown-item" href="#">Hapus</a>
                        <a class="dropdown-item" href="{{ route('post.index') }}">Kembali</a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12">
                        <b>{{ $post->user->name }}</b> - {{ $post->title }}<br>
                        <span class="date-post">{{ \Carbon\Carbon::parse($post->created_at)->diffForHumans() }}</span>
                        <br>
                        {{ $post->description }} <br>
                        @foreach($media_path as $key => $media)
                            @php
                                $key++;
                                $separator = ', ';
                                if ($key === count($media_path)) {
                                    $separator = '';
                                }
                            @endphp
                            <a href="{{ $media }}">Attachment {{ $key }}{{$separator}}</a>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <div id="form-comment">
                </div>

                <div id="comments" style="margin-top: 20px">
                </div>
            </div>
        </div>

        <div class="form-clone" style="display: none">
            <form action="{{ route('post.comment.submit', ['post' => $post->id]) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="comment_id" value=":value">
                <div class="form-group">
                    <textarea class="form-control" name="comment" placeholder="Tulis komentar disini"></textarea>
                </div>
                <div class="form-group">
                    <input type="file" multiple name="media[]" class="form-control">
                </div>
                <button type="button" class="btn btn-primary submit-button">Simpan</button>
            </form>
        </div>
    </div>
@endsection

@push('js')
    <script>
        let postURL = $('#post').data('post-url');
        $(document).ready(function () {
            loadComment(postURL)

            // default form comment
            let form = $( ".form-clone" ).html();
            form = form.replace(':value', '');
            $('#form-comment').append(form);

            $(document).on('click', '.reply', function(){
                let divParent = $(this).parent('div')[0];
                let formId = `#${divParent.id}`;
                // prevent form display more than one
                if ( $(formId).children().length > 2 ) {
                    $(formId).children('form:first').remove();
                    return;
                }

                let formNested = $( ".form-clone" ).html();
                formNested = formNested.replace(":value", $(formId).data('comment-id'))
                $(formId).append(formNested);
            });

            $(document).on('click', '.submit-button', function (e) {
                e.preventDefault()
                addComment($(this).parents("form")[0])
            })
        });

        function loadComment(postURL) {
            $.ajax({
                method: "GET",
                url: postURL
            }).done(function (comments) {
                if (comments.length > 0) {
                    renderComment(comments)
                } else {
                    // action jika komen kosong
                }
            })
        }

        function addComment(form) {
            let url = form.action
            let formData = new FormData(form)

            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                enctype: 'multipart/form-data',
            }).done(function (data) {
                form.reset()
                loadComment(postURL)
            });
        }

        var marginLeft = 0;

        function renderComment(comments) {
            let commentHtml = '';
            for (const comment of comments) {
                marginLeft = -15
                commentHtml += format(commentHtml, comment)
            }

            $('#comments').html(commentHtml)
        }

        function format(html, comment) {
            let mediaLink = '';
            for(let [index, media] of comment.media.entries()) {
                index += 1;
                let separator = ',&nbsp';
                if (index === comment.media.length) {
                    separator = '';
                }

                mediaLink += `<a href="${media}" target="_blank">Attachment ${index}</a>${separator}`;
            }

            html = `<div class="row" style="margin-top: 15px; margin-left: ${marginLeft}px">`+
                `<div class="col-md-12"><b>${comment.commentator.name}</b><br>`+
                `<div class="date-post">${comment.created_at}</div>`+
                `${comment.comment}<br>`+
                `${mediaLink}`+
                `</div>`+
                `<div class="col-md-12" data-comment-id="${comment.id}" id="form-comment-${comment.id}"><span class="reply">Balas</span><br></div>`+
                `</div>`

            let marginLeftCopy = marginLeft;
            if (comment.nested.length > 0) {
                marginLeft += 20;
                for (const cmt of comment.nested) {
                    html += format(html, cmt)
                }
                marginLeft = marginLeftCopy;
            }

            return html;
        }

        function deleteComment() {

        }
    </script>
@endpush

