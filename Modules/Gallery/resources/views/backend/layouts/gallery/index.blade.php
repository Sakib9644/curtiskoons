@extends('backend.app', ['title' => 'Image Gallery'])
@section('style')
<style>
    .icon-btn:hover {
        background-color: #f0f0f0;
        transform: scale(1.1);
        transition: all 0.2s ease-in-out;
    }
</style>
@section('content')

<!--app-content open-->
<div class="app-content main-content mt-0">
    <div class="side-app">

        <!-- CONTAINER -->
        <div class="main-container container-fluid">

            <div class="page-header">
                <div>
                    <h1 class="page-title">Image Gallery</h1>
                </div>
                <div class="ms-auto pageheader-btn">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Image</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Gallery</li>
                    </ol>
                </div>
            </div>

            <div class="row" id="user-profile">
                <div class="col-lg-12">
                    <div class="card post-sales-main">
                        <div class="card-body border-0">
                            <div class="form-group">
                                <input type="file" class="dropify form-control" name="images[]" id="images" multiple />
                                <p class="textTransform">Image Size Less than 5MB and Image Type must be jpeg,jpg,png.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row" id="user-profile">
                <div class="col-lg-12" style="text-align: center">
                    <div id="image_load"></div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12 mb-2" id="pagenation" style="text-align: center">
                    <!-- Pagination will be dynamically generated here -->
                </div>
            </div>

        </div>
    </div>
</div>
<!-- CONTAINER CLOSED -->
@endsection
@push('scripts')
<script>
    let global_page = 1;

    function imagesLoad(page = 1) {
        NProgress.start();
        $('#image_load').empty();
        $.ajax({
            url: `{{ route('ajax.gallery.all') }}?page=${page}`,
            type: 'GET',
            success: function(resp) {
                NProgress.done();
                
                global_page = resp.files.current_page;
                if (page > resp.files.last_page) {
                    imagesLoad(resp.files.last_page);
                }

                // Clear previous images
                let imagehtml = '';
                $.each(resp.files.data, function(key, image) {
                    imagehtml += `<div style="height: 150px; width: 150px; object-fit: cover; display: inline-block; margin: 5px">
                                <div class="position-relative">
                                    <img onclick="alert('` + image.id + `')" data-src="` + image.path + `" src="` + image.path + `" alt="post image" class="img-fluid img-thumbnail" style="height: 150px; width: 150px; object-fit: cover; cursor: pointer;">

                                    <!-- Trash Icon -->
                                    <i class="fa fa-trash position-absolute top-0 start-0 d-flex align-items-center justify-content-center bg-white text-danger p-1 rounded-circle icon-btn"
                                    style="cursor: pointer; width: 30px; height: 30px;"
                                    data-id="` + image.id + `"
                                    title="Delete Image"
                                    onclick="deleteImage(event, this)">
                                    </i>

                                    <!-- Link Icon -->
                                    <a href="` + image.path + `" target="_blank"
                                    class="position-absolute top-0 end-0 d-flex align-items-center justify-content-center bg-white text-info p-1 rounded-circle icon-btn"
                                    style="cursor: pointer; width: 30px; height: 30px;">
                                        <i class="fa-solid fa-link"></i>
                                    </a>

                                </div>
                            </div>`;
                });
                $('#image_load').html(imagehtml);

                // Pagination
                let paginationHtml = '';
                if (resp.files.last_page > 1) {
                    const currentPage = resp.files.current_page;
                    const totalPages = resp.files.last_page;
                    const delta = 2;
                    const range = [];
                    const rangeWithDots = [];

                    range.push(1);

                    if (currentPage - delta > 2) {
                        range.push('...');
                    }

                    for (
                        let i = Math.max(2, currentPage - delta); i <= Math.min(currentPage + delta, totalPages - 1); i++
                    ) {
                        range.push(i);
                    }

                    if (currentPage + delta < totalPages - 1) {
                        range.push('...');
                    }

                    if (totalPages > 1) {
                        range.push(totalPages);
                    }

                    for (let i = 0; i < range.length; i++) {
                        if (range[i] === currentPage) {
                            rangeWithDots.push(`<button class="btn btn-primary" onclick="imagesLoad(${range[i]})">${range[i]}</button>`);
                        } else if (typeof range[i] === 'number') {
                            rangeWithDots.push(`<button class="btn btn-secondary" onclick="imagesLoad(${range[i]})">${range[i]}</button>`);
                        } else {
                            rangeWithDots.push(`<button class="btn btn-secondary" disabled>${range[i]}</button>`);
                        }
                    }

                    paginationHtml += rangeWithDots.join('');
                }
                $('#pagenation').html(paginationHtml);

            },
            error: function(resp) {
                NProgress.done();
                $('#image_load').html(resp.message);
            }
        });
    }

    imagesLoad();

    function deleteImage(event) {
        event.preventDefault();
        if (confirm("Are you sure you want to delete this image?")) {
            NProgress.start();
            let id = $(event.target).data('id');

            $.ajax({
                url: `{{ route('ajax.gallery.destroy', ':id') }}`.replace(':id', id),
                type: 'GET',
                success: function(resp) {
                    NProgress.done();
                    console.log(global_page);
                    imagesLoad(global_page);
                    toastr.success(resp.message);
                },
                error: function(resp) {
                    NProgress.done();
                    toastr.error(resp.message);
                }
            });
        }
    }

    $('#images').change(function() {
        let data = new FormData();
        let files = $(this)[0].files;

        for (let i = 0; i < files.length; i++) {
            data.append('images[]', files[i]);
        }

        NProgress.start();

        $.ajax({
            url: `{{ route('ajax.gallery.store') }}`,
            type: 'POST',
            data: data,
            contentType: false,
            processData: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // Add CSRF token
            },
            success: function(resp) {
                NProgress.done();
                $('.dropify-clear').click();
                $('#image_load').empty();
                $('#pagenation').empty();
                imagesLoad();
                toastr.success(resp.message);
            },
            error: function(xhr) {
                NProgress.done();
                let errorMsg = xhr.responseJSON?.message || 'Upload failed';
                toastr.error(errorMsg);
            }
        });
    });
</script>
@endpush