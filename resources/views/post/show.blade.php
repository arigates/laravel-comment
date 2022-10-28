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
                        <li class="dropdown-item" style="cursor: pointer" href="{{ route('post.edit', ['post' => $post->id]) }}" id="edit-post">Edit</li>
                        <li class="dropdown-item" style="cursor: pointer" href="{{ route('post.delete', ['post' => $post->id]) }}" id="delete-post">Hapus</li>
                        <a class="dropdown-item" href="{{ route('post.index') }}" id="back">Kembali</a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row" id="post-body">
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
                <div class="row" id="post-form-edit" style="display: none">
                    <div class="col-12">
                        <form action="{{ route('post.update', ['post' => $post->id]) }}" method="POST" enctype="multipart/form-data" method="POST" id="form-edit-post">
                            @csrf
                            <div class="form-group">
                                <input class="form-control" name="title" id="edit-title" placeholder="Tulis judul disini" required>
                            </div>
                            <div class="form-group">
                                <textarea class="form-control" name="description" id="edit-description" placeholder="Tulis deskripsi disini" required></textarea>
                            </div>
                            <div class="form-group" id="attachment-render">

                            </div>
                            <div class="form-group">
                                <input type="file" multiple name="media[]" class="form-control">
                            </div>
                            <button type="submit" class="btn btn-primary">Bagikan</button>
                            <button type="button" class="btn btn-danger" id="cancel-edit">Batalkan</button>
                        </form>
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
        <div class="form-clone-comment" style="display: none">
            <form action=":action" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <textarea class="form-control" name="comment" placeholder="Tulis komentar disini">:comment</textarea>
                </div>
                <div class="form-group">
                    :attachment
                </div>
                <div class="form-group">
                    <input type="file" multiple name="media[]" class="form-control">
                </div>
                <button type="button" class="btn btn-primary submit-button">Simpan</button>
                <button type="button" class="btn btn-danger cancel-edit-comment">Batalkan</button>
            </form>
        </div>
    </div>
@endsection

@push('js')
    <script>
        let postURL = $('#post').data('post-url');
        let cloneComment = '';
        let reRenderComment = false;
        let marginLeft = 0;

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
                if ( $(formId).children().length > 4 ) {
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

            $(document).on('click', '.delete-comment', function () {
                let commentId = $(this).data('comment-id');
                deleteComment(commentId)
            })

            $(document).on('click', '.edit-comment', function () {
                if (cloneComment !== '') {
                    $('.cancel-edit-comment:first').click()
                }

                let commentId = $(this).data('comment-id');
                let parent = $(this).parent().parent('div');
                cloneComment = parent.html();
                let form = $('.form-clone-comment').html();
                let url = '{{ route('post.comment.edit', ['comment' => ':comment']) }}'
                url = url.replace(':comment', commentId)

                $.ajax({
                    url: url,
                    type: 'GET',
                }).done(function (data) {
                    form = form.replace(':action', url)
                    form = form.replace(':comment', data.comment)

                    let media = data.media
                    let mediaHtml = '';
                    if (media !== "") {
                        let index = 1;
                        for (let mdi of media) {
                            mediaHtml += `<span class="delete-attachment-comment" data-comment-id="${commentId}" data-attachment="${mdi}">Attachment ${index}</span> `
                            index += 1;
                        }
                    }

                    form = form.replace(':attachment', mediaHtml);
                    parent.html(form)
                });
            })

            $(document).on('click', '.cancel-edit-comment', function () {
                if (reRenderComment === true) {
                    loadComment(postURL)
                    cloneComment = '';
                    reRenderComment = false
                    return;
                }

                let divComment = $(this).parent().parent('div');
                divComment.html(cloneComment);
                cloneComment = '';
            })

            $(document).on('click', '.delete-attachment', function () {
                let attachment = $(this).data('attachment')
                let url = '{{ route('post.delete.attachment', ['post' => $post->id, 'attachment' => ':attachment']) }}';
                url = url.replace(':attachment', attachment)
                let currentClicked = $(this)

                $.ajax({
                    url: url,
                    type: 'DELETE',
                }).done(function (data) {
                    currentClicked.remove()
                });
            })

            $(document).on('click', '.delete-attachment-comment', function () {
                let commentId = $(this).data('comment-id');
                let attachment = $(this).data('attachment');
                let url = '{{ route('post.comment.delete.attachment', ['comment' => ':comment', 'attachment' => ':attachment']) }}'

                url = url.replace(':comment', commentId)
                url = url.replace(':attachment', attachment)

                let currentClicked = $(this)

                $.ajax({
                    url: url,
                    type: 'DELETE',
                }).done(function (data) {
                    currentClicked.remove();
                    reRenderComment = true;
                });
            });
        });

        $('#form-edit-post').submit(function (e) {
            e.preventDefault()
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
                window.location.reload()
            });
        })

        $('#edit-post').click(function () {
            let url = $(this).attr('href');
            $.ajax({
                url: url,
                type: 'GET',
            }).done(function (data) {
                $('#edit-title').val(data.title)
                $('#edit-description').val(data.description)

                let media = data.media
                let mediaHtml = '';
                if (media !== "") {
                    let index = 1;
                    for (let mdi of media) {
                        mediaHtml += `<span class="delete-attachment" data-attachment="${mdi}">Attachment ${index}</span> `
                        index += 1;
                    }
                }

                $('#attachment-render').html(mediaHtml)
                $('#post-body').hide();
                $('#post-form-edit').show();
            });
        });

        $('#cancel-edit').click(function () {
            $('#post-body').show();
            $('#post-form-edit').hide();
        });

        $('#delete-post').click( function () {
            let url = $(this).attr('href');
            let urlBack = $('#back').attr('href');

            $.ajax({
                url: url,
                type: 'DELETE',
            }).done(function (data) {
                window.location.href = urlBack;
            });
        })

        function loadComment(postURL) {
            $.ajax({
                method: "GET",
                url: postURL
            }).done(function (comments) {
                cloneComment = ''
                reRenderComment = false
                renderComment(comments)
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

            let editComment = '';
            if (comment.can_edit === true) {
                editComment = `<span class="edit-comment" data-comment-id="${comment.id}">Edit</span>`;
            }

            let deleteComment = '';
            if (comment.can_delete === true) {
                deleteComment = `<span class="delete-comment" data-comment-id="${comment.id}">Hapus</span>`;
            }

            html = `<div class="row" style="margin-top: 15px; margin-left: ${marginLeft}px">`+
                `<div class="col-md-12"><b>${comment.commentator.name}</b><br>`+
                `<div class="date-post">${comment.created_at}</div>`+
                `<div class="comment">${comment.comment}</div>`+
                `${mediaLink}`+
                `<div data-comment-id="${comment.id}" id="form-comment-${comment.id}"><span class="reply">Balas</span>`+
                `${editComment}${deleteComment}<br></div>`+
                `</div>`+
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

        function deleteComment(commentId) {
            let url = '{{ route('post.comment.delete', ['comment' => ':id']) }}'
            url = url.replace(':id', commentId)

            $.ajax({
                url: url,
                type: 'DELETE',
            }).done(function (data) {
                loadComment(postURL)
            });
        }
    </script>
@endpush

