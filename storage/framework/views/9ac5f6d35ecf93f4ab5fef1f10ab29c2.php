	<!-- Bootstrap JS -->
	<script src="<?php echo e(versionedAsset('assets/js/bootstrap.bundle.min.js')); ?>"></script>
	<!--plugins-->
	<script src="<?php echo e(versionedAsset('assets/js/jquery.min.js')); ?>"></script>
	<script src="<?php echo e(versionedAsset('assets/plugins/simplebar/js/simplebar.min.js')); ?>"></script>
	<script src="<?php echo e(versionedAsset('assets/plugins/metismenu/js/metisMenu.min.js')); ?>"></script>
	<script src="<?php echo e(versionedAsset('assets/plugins/perfect-scrollbar/js/perfect-scrollbar.js')); ?>"></script>
	<script src="<?php echo e(versionedAsset('assets/plugins/vectormap/jquery-jvectormap-2.0.2.min.js')); ?>"></script>
    <script src="<?php echo e(versionedAsset('assets/plugins/vectormap/jquery-jvectormap-world-mill-en.js')); ?>"></script>
	<script src="<?php echo e(versionedAsset('assets/plugins/chartjs/js/chart.js')); ?>"></script>
    <!-- select2 -->
    <script src="<?php echo e(versionedAsset('custom/libraries/select2-theme/select2-4.1.0-rc.0/dist/js/select2.min.js')); ?>"></script>
    <!-- Sweetalert -->
    <script src="<?php echo e(versionedAsset('custom/libraries/sweetalert/sweetalert.min.js')); ?>"></script>
	<!-- Notification Toast -->
    <script src="<?php echo e(versionedAsset('custom/libraries/iziToast/dist/js/iziToast.min.js')); ?>"></script>
    <!-- Date & Time Picker -->
    <script src="<?php echo e(versionedAsset('custom/libraries/flatpickr/flatpickr.min.js')); ?>"></script>
    <!-- Autocomplete -->
	<script src="<?php echo e(versionedAsset('assets/plugins/jquery-ui/jquery-ui.js')); ?>"></script>
    <!-- Number Library -->
    <script src="<?php echo e(versionedAsset('custom/libraries/numbro/numbro.min.js')); ?>"></script>
    <!-- All libraries Settings -->
    <script src="<?php echo e(versionedAsset('custom/js/plugin-settings.js')); ?>"></script>

    <script type="text/javascript">
		/*Configure the Application Date Format*/
		var appCompanyName = "<?php echo e(app('company')['name']); ?>";
		var appTaxType = "<?php echo e(app('company')['tax_type']); ?>";
		var dateFormatOfApp = "<?php echo e(app('company')['date_format']); ?>";
		var numberPrecision = <?php echo e(app('company')['number_precision']); ?>;
		var quantityPrecision = <?php echo e(app('company')['quantity_precision']); ?>;
		var itemSettings = {
			show_sku : <?php echo e(app('company')['show_sku']); ?>,
			show_mrp : <?php echo e(app('company')['show_mrp']); ?>,
			show_discount : <?php echo e(app('company')['show_discount']); ?>,
			enable_serial_tracking : <?php echo e(app('company')['enable_serial_tracking']); ?>,
			enable_batch_tracking : <?php echo e(app('company')['enable_batch_tracking']); ?>,
			enable_mfg_date : <?php echo e(app('company')['enable_mfg_date']); ?>,
			enable_exp_date : <?php echo e(app('company')['enable_exp_date']); ?>,
			enable_color : <?php echo e(app('company')['enable_color']); ?>,
			enable_size : <?php echo e(app('company')['enable_size']); ?>,
			enable_model : <?php echo e(app('company')['enable_model']); ?>,
		};
		var baseURL = '<?php echo e(url('')); ?>';
        var _csrf_token = '<?php echo e(csrf_token()); ?>';
        var allowUserToPurchaseDiscount = <?php echo e(auth()->check() && auth()->user()->can('general.permission.to.apply.discount.to.purchase') ? 1 : 0); ?>;
        var allowUserToSaleDiscount = <?php echo e(auth()->check() && auth()->user()->can('general.permission.to.apply.discount.to.sale') ? 1 : 0); ?>;
        var isEnableSecondaryCurrency = <?php echo e(auth()->check() && app('company')['is_enable_secondary_currency'] ? 1 : 0); ?>;
        var isEnableCarrierCharge = <?php echo e(auth()->check() && app('company')['is_enable_carrier_charge'] ? 1 : 0); ?>;
	</script>
    <!-- Clear Cache -->
    <script src="<?php echo e(versionedAsset('custom/js/cache.js')); ?>"></script>

	<?php echo $__env->yieldContent('js'); ?>
	<!--app JS-->
	<?php if($appDirection=='ltr'): ?>
		<script src="<?php echo e(versionedAsset('assets/js/app.js')); ?>"></script>
	<?php else: ?>
		<script src="<?php echo e(versionedAsset('assets/rtl/js/app.js')); ?>"></script>
	<?php endif; ?>

	<!-- Custom Library -->
	<script src="<?php echo e(versionedAsset('custom/js/custom.js')); ?>"></script>
<?php /**PATH C:\xampp\htdocs\faster_system\resources\views/layouts/script.blade.php ENDPATH**/ ?>