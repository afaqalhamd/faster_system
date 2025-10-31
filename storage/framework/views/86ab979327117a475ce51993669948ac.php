<head>
	<!-- Required meta tags -->
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
	<!--favicon-->
	<link rel="icon" href='<?php echo e(url("/fevicon/" . $fevicon)); ?>'  type="image/png" />
	<!--plugins-->
	<link href="<?php echo e(versionedAsset('assets/plugins/vectormap/jquery-jvectormap-2.0.2.css')); ?>" rel="stylesheet" />
	<link href="<?php echo e(versionedAsset('assets/plugins/simplebar/css/simplebar.css')); ?>" rel="stylesheet" />
	<link href="<?php echo e(versionedAsset('assets/plugins/perfect-scrollbar/css/perfect-scrollbar.css')); ?>" rel="stylesheet" />
	<link href="<?php echo e(versionedAsset('assets/plugins/metismenu/css/metisMenu.min.css')); ?>" rel="stylesheet" />
    <!-- Select2 -->
    <link rel="stylesheet" href="<?php echo e(versionedAsset('custom/libraries/select2-theme/select2-4.1.0-rc.0/dist/css/select2.min.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(versionedAsset('custom/libraries/select2-theme/select2-bootstrap-5-theme-1.3.0/dist/select2-bootstrap-5-theme.min.css')); ?>">
	<!-- loader-->
	<script src="<?php echo e(versionedAsset('assets/js/pace.min.js')); ?>"></script>
	<link href="<?php echo e(versionedAsset('assets/css/pace.min.css')); ?>" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Cairo&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="preload" href="/fonts/Cairo-Regular.ttf" as="font" type="font/ttf" crossorigin>
	<!-- Bootstrap CSS -->
	<?php if($appDirection=='ltr'): ?>
	<link href="<?php echo e(versionedAsset('assets/css/bootstrap.min.css')); ?>" rel="stylesheet">
	<link href="<?php echo e(versionedAsset('assets/css/bootstrap-extended.css')); ?>" rel="stylesheet">
	<link href="<?php echo e(versionedAsset('assets/css/app.css')); ?>" rel="stylesheet">
	<?php else: ?>
	<link href="<?php echo e(versionedAsset('assets/rtl/css/bootstrap.min.css')); ?>" rel="stylesheet">
	<link href="<?php echo e(versionedAsset('assets/rtl/css/bootstrap-extended.css')); ?>" rel="stylesheet">
	<link href="<?php echo e(versionedAsset('assets/rtl/css/app.css')); ?>" rel="stylesheet">
	<?php endif; ?>
	<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
	<link href="<?php echo e(versionedAsset('assets/css/icons.css')); ?>" rel="stylesheet">
	<!-- Notification Toast -->
    <link rel="stylesheet" href="<?php echo e(versionedAsset('custom/libraries/iziToast/dist/css/iziToast.min.css')); ?>">
    <!-- Date & Time Picker -->
    <link rel="stylesheet" href="<?php echo e(versionedAsset('custom/libraries/flatpickr/flatpickr.min.css')); ?>">
    <!-- Autocomplete -->
    <link href="<?php echo e(versionedAsset('assets/plugins/jquery-ui/jquery-ui.css')); ?>" rel="stylesheet" />
	<!-- Theme Style CSS -->
	<link rel="stylesheet" href="<?php echo e(versionedAsset('assets/css/dark-theme.css')); ?>"/>
	<link rel="stylesheet" href="<?php echo e(versionedAsset('assets/css/semi-dark.css')); ?>"/>
	<link rel="stylesheet" href="<?php echo e(versionedAsset('assets/css/header-colors.css')); ?>"/>
	<!-- Flags CSS -->
	<link rel="stylesheet" href="<?php echo e(versionedAsset('custom/libraries/flag-icons-main/css/flag-icons.min.css')); ?>">
	<!-- Custom CSS -->
	<link rel="stylesheet" href="<?php echo e(versionedAsset('custom/css/custom.css')); ?>">
	<?php echo $__env->yieldContent('css'); ?>
	<title><?php echo $__env->yieldContent('title', app('company')['name'] ?? 'Faster System'); ?></title>
</head>
<?php /**PATH C:\xampp\htdocs\faster_system\resources\views/layouts/head.blade.php ENDPATH**/ ?>