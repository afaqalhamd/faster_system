@extends('layouts.app')
@section('title', __('الإشعارات'))

@section('content')
<!--start page wrapper -->
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumb :langArray="[
                                'app.utilities',
                                'الإشعارات',
                            ]"/>
        <div class="row">
            <form class="row g-3 needs-validation" id="notificationForm" action="{{ route('notifications.send') }}" method="POST">
                {{-- CSRF Protection --}}
                @csrf
                @method('POST')

                <input type="hidden" id="base_url" value="{{ url('/') }}">
                <div class="col-12 col-lg-12">
                    @include('layouts.session')

                    <div class="card">
                        <div class="card-header px-4 py-3">
                            <h5 class="mb-0">إرسال إشعار تجريبي</h5>
                        </div>
                        <div class="card-body p-4 row g-3">
                            <div class="col-md-12">
                                <div class="alert border-0 border-start border-5 border-info alert-dismissible fade show py-2">
                                    <div class="d-flex align-items-center">
                                        <div class="font-35 text-info"><i class="fas fa-mobile-alt"></i></div>
                                        <div class="ms-3">
                                            <h6 class="mb-0 text-info">الأجهزة النشطة</h6>
                                            <div>عدد الأجهزة النشطة: <strong>{{ $deviceTokensCount }}</strong></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <x-label for="title" name="عنوان الإشعار" />
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>

                            <div class="col-md-12">
                                <x-label for="body" name="محتوى الإشعار" />
                                <textarea class="form-control" id="body" name="body" rows="3" required></textarea>
                            </div>

                            <div class="col-md-12">
                                <x-label for="user_id" name="إرسال إلى مستخدم محدد (اختياري)" />
                                <select class="form-select" id="user_id" name="user_id">
                                    <option value="">جميع المستخدمين</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="card-body p-4 row g-3">
                            <div class="col-md-12">
                                <div class="d-md-flex d-grid align-items-center gap-3">
                                    <x-button type="submit" class="primary px-4" text="إرسال الإشعار" />
                                    <x-anchor-tag href="{{ route('dashboard') }}" text="{{ __('app.close') }}" class="btn btn-light px-4" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <!--end row-->
    </div>
</div>
@endsection

@section('js')
<script>
    $(function() {
        // يمكن إضافة أي سكريبت خاص بالصفحة هنا
    });
</script>
@endsection