@extends('layouts.app')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <!-- Page Header -->
                    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-4">
                        <div class="breadcrumb-title pe-3">تواصل معنا</div>
                        <div class="ps-3">
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-0 p-0">
                                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                                    <li class="breadcrumb-item active" aria-current="page">تواصل معنا</li>
                                </ol>
                            </nav>
                        </div>
                    </div>

                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <!-- Contact Info Cards -->
                    <div class="row mb-4">
                        <div class="col-md-4 mb-3">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body text-center">
                                    <div class="mb-3">
                                        <i class="bx bx-phone-call text-primary" style="font-size: 3rem;"></i>
                                    </div>
                                    <h5 class="card-title">الهاتف</h5>
                                    <p class="card-text text-muted">+966 XX XXX XXXX</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body text-center">
                                    <div class="mb-3">
                                        <i class="bx bx-envelope text-primary" style="font-size: 3rem;"></i>
                                    </div>
                                    <h5 class="card-title">البريد الإلكتروني</h5>
                                    <p class="card-text text-muted">info@example.com</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body text-center">
                                    <div class="mb-3">
                                        <i class="bx bx-map text-primary" style="font-size: 3rem;"></i>
                                    </div>
                                    <h5 class="card-title">العنوان</h5>
                                    <p class="card-text text-muted">الرياض، المملكة العربية السعودية</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Form -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <h4 class="card-title mb-4">
                                <i class="bx bx-message-square-dots me-2"></i>أرسل لنا رسالة
                            </h4>

                            <form action="{{ route('contact.store') }}" method="POST">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="name" class="form-label">الاسم الكامل <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                                               id="name" name="name" value="{{ old('name') }}" required>
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label for="email" class="form-label">البريد الإلكتروني <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control @error('email') is-invalid @enderror"
                                               id="email" name="email" value="{{ old('email') }}" required>
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label for="phone" class="form-label">رقم الهاتف</label>
                                        <input type="text" class="form-control @error('phone') is-invalid @enderror"
                                               id="phone" name="phone" value="{{ old('phone') }}">
                                        @error('phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label for="subject" class="form-label">الموضوع <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('subject') is-invalid @enderror"
                                               id="subject" name="subject" value="{{ old('subject') }}" required>
                                        @error('subject')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-12">
                                        <label for="message" class="form-label">الرسالة <span class="text-danger">*</span></label>
                                        <textarea class="form-control @error('message') is-invalid @enderror"
                                                  id="message" name="message" rows="6" required>{{ old('message') }}</textarea>
                                        @error('message')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary px-5">
                                            <i class="bx bx-send me-2"></i>إرسال الرسالة
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Map Section (Optional) -->
                    <div class="card border-0 shadow-sm mt-4">
                        <div class="card-body p-0">
                            <div style="height: 400px; background: #f0f0f0; display: flex; align-items: center; justify-content: center;">
                                <div class="text-center text-muted">
                                    <i class="bx bx-map" style="font-size: 4rem;"></i>
                                    <p class="mt-2">يمكنك إضافة خريطة Google Maps هنا</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }

    .form-control:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
</style>
@endsection
