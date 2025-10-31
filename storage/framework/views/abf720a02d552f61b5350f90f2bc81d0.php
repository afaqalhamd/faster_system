<?php $__env->startSection('title', __('auth.login')); ?>

<?php $__env->startSection('container'); ?>

	<!--wrapper-->
	<div class="wrapper">
		<div class="section-authentication-cover bg-white">
			<div class="">
				<div class="row g-0">

					<div class="col-12 col-xl-7 col-xxl-8 auth-cover-left align-items-center justify-content-center d-none d-xl-flex bg-white">

                        <div class="card shadow-none bg-white rounded-0 mb-0">
							<div class="card-body">
                                <script src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.6.2/dist/dotlottie-wc.js" type="module"></script>
                                <dotlottie-wc src="https://lottie.host/1c9bea6f-8e27-4e58-8d9a-3fc8c2773c68/kvp5jDakgw.lottie" style="width: 650px;height: 650px" speed="1" autoplay loop></dotlottie-wc>
							</div>
						</div>

					</div>

					<div class="col-12 col-xl-5 col-xxl-4 auth-cover-right align-items-center justify-content-center">

						<div class="card rounded-0 m-3 shadow-none bg-white mb-0">
							<?php if(config('demo.enabled')): ?>
						<div class="position-absolute top-0 end-0 mt-3 me-3">
					      <div class="d-grid">
					        <a href="https://codecanyon.net/item/delta/51635135" target="_blank" class="btn btn-success btn-sm px-4">Buy Now</a>
					      </div>
					    </div>
					    <?php endif; ?>
							<div class="card-body p-sm-5">

								<?php echo $__env->make('layouts.session', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

								<div class="">
									<div class="mb-3 text-center">
										<img src=<?php echo e(url("/app/getimage/" . app('site')['colored_logo'])); ?> width="60" alt="">
									</div>
									<div class="text-center mb-4">
										<p class="mb-0"><?php echo e(__('auth.login_to_account')); ?></p>
									</div>
									<div class="form-body">
										<form class="row g-3" id="loginForm" action="<?php echo e(route('login')); ?>" enctype="multipart/form-data">
											
                        					<?php echo csrf_field(); ?>
                        					<?php echo method_field('POST'); ?>

											<div class="col-12">
												<?php if (isset($component)) { $__componentOriginal109ed44299a070e6f2e0a484dff15187 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal109ed44299a070e6f2e0a484dff15187 = $attributes; } ?>
<?php $component = App\View\Components\Label::resolve(['for' => 'email','name' => ''.e(__('app.email')).''] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(App\View\Components\Label::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal109ed44299a070e6f2e0a484dff15187)): ?>
<?php $attributes = $__attributesOriginal109ed44299a070e6f2e0a484dff15187; ?>
<?php unset($__attributesOriginal109ed44299a070e6f2e0a484dff15187); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal109ed44299a070e6f2e0a484dff15187)): ?>
<?php $component = $__componentOriginal109ed44299a070e6f2e0a484dff15187; ?>
<?php unset($__componentOriginal109ed44299a070e6f2e0a484dff15187); ?>
<?php endif; ?>
												<?php if (isset($component)) { $__componentOriginal786b6632e4e03cdf0a10e8880993f28a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal786b6632e4e03cdf0a10e8880993f28a = $attributes; } ?>
<?php $component = App\View\Components\Input::resolve(['placeholder' => 'Enter Email','id' => 'email','name' => 'email','type' => 'email','required' => true,'autofocus' => true,'autocomplete' => true] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(App\View\Components\Input::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal786b6632e4e03cdf0a10e8880993f28a)): ?>
<?php $attributes = $__attributesOriginal786b6632e4e03cdf0a10e8880993f28a; ?>
<?php unset($__attributesOriginal786b6632e4e03cdf0a10e8880993f28a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal786b6632e4e03cdf0a10e8880993f28a)): ?>
<?php $component = $__componentOriginal786b6632e4e03cdf0a10e8880993f28a; ?>
<?php unset($__componentOriginal786b6632e4e03cdf0a10e8880993f28a); ?>
<?php endif; ?>
											</div>

											<div class="col-12">
												<?php if (isset($component)) { $__componentOriginal109ed44299a070e6f2e0a484dff15187 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal109ed44299a070e6f2e0a484dff15187 = $attributes; } ?>
<?php $component = App\View\Components\Label::resolve(['for' => 'password','name' => ''.e(__('app.password')).''] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(App\View\Components\Label::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal109ed44299a070e6f2e0a484dff15187)): ?>
<?php $attributes = $__attributesOriginal109ed44299a070e6f2e0a484dff15187; ?>
<?php unset($__attributesOriginal109ed44299a070e6f2e0a484dff15187); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal109ed44299a070e6f2e0a484dff15187)): ?>
<?php $component = $__componentOriginal109ed44299a070e6f2e0a484dff15187; ?>
<?php unset($__componentOriginal109ed44299a070e6f2e0a484dff15187); ?>
<?php endif; ?>
												<div class="input-group" id="show_hide_password">
													<?php if (isset($component)) { $__componentOriginal786b6632e4e03cdf0a10e8880993f28a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal786b6632e4e03cdf0a10e8880993f28a = $attributes; } ?>
<?php $component = App\View\Components\Input::resolve(['placeholder' => 'Enter Password','id' => 'password','name' => 'password','type' => 'password','required' => true] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(App\View\Components\Input::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal786b6632e4e03cdf0a10e8880993f28a)): ?>
<?php $attributes = $__attributesOriginal786b6632e4e03cdf0a10e8880993f28a; ?>
<?php unset($__attributesOriginal786b6632e4e03cdf0a10e8880993f28a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal786b6632e4e03cdf0a10e8880993f28a)): ?>
<?php $component = $__componentOriginal786b6632e4e03cdf0a10e8880993f28a; ?>
<?php unset($__componentOriginal786b6632e4e03cdf0a10e8880993f28a); ?>
<?php endif; ?>
													<a href="javascript:;" class="input-group-text bg-transparent"><i class="bx bx-hide"></i></a>

												</div>
											</div>
											<div class="col-md-6">
												<?php if (isset($component)) { $__componentOriginalaaa71e56cc71e4546988717dd004610d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaaa71e56cc71e4546988717dd004610d = $attributes; } ?>
<?php $component = App\View\Components\RadioBlock::resolve(['id' => 'remember','boxName' => 'remember','text' => ''.e(__('auth.remember_me')).'','parentDivClass' => 'form-switch'] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('radio-block'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(App\View\Components\RadioBlock::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalaaa71e56cc71e4546988717dd004610d)): ?>
<?php $attributes = $__attributesOriginalaaa71e56cc71e4546988717dd004610d; ?>
<?php unset($__attributesOriginalaaa71e56cc71e4546988717dd004610d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalaaa71e56cc71e4546988717dd004610d)): ?>
<?php $component = $__componentOriginalaaa71e56cc71e4546988717dd004610d; ?>
<?php unset($__componentOriginalaaa71e56cc71e4546988717dd004610d); ?>
<?php endif; ?>
											</div>
											<div class="col-md-6 text-end">
												<?php if (isset($component)) { $__componentOriginal08be1f47856809f4c6eda68811b93273 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal08be1f47856809f4c6eda68811b93273 = $attributes; } ?>
<?php $component = App\View\Components\AnchorTag::resolve(['href' => ''.e(route('password.request')).'','text' => ''.e(__('auth.forgot_password')).''] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('anchor-tag'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(App\View\Components\AnchorTag::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal08be1f47856809f4c6eda68811b93273)): ?>
<?php $attributes = $__attributesOriginal08be1f47856809f4c6eda68811b93273; ?>
<?php unset($__attributesOriginal08be1f47856809f4c6eda68811b93273); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal08be1f47856809f4c6eda68811b93273)): ?>
<?php $component = $__componentOriginal08be1f47856809f4c6eda68811b93273; ?>
<?php unset($__componentOriginal08be1f47856809f4c6eda68811b93273); ?>
<?php endif; ?>
											</div>
											<div class="col-12">
												<div class="d-grid">
													<?php if (isset($component)) { $__componentOriginale67687e3e4e61f963b25a6bcf3983629 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale67687e3e4e61f963b25a6bcf3983629 = $attributes; } ?>
<?php $component = App\View\Components\Button::resolve(['type' => 'submit','class' => 'primary','text' => ''.e(__('app.sign_in')).''] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(App\View\Components\Button::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale67687e3e4e61f963b25a6bcf3983629)): ?>
<?php $attributes = $__attributesOriginale67687e3e4e61f963b25a6bcf3983629; ?>
<?php unset($__attributesOriginale67687e3e4e61f963b25a6bcf3983629); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale67687e3e4e61f963b25a6bcf3983629)): ?>
<?php $component = $__componentOriginale67687e3e4e61f963b25a6bcf3983629; ?>
<?php unset($__componentOriginale67687e3e4e61f963b25a6bcf3983629); ?>
<?php endif; ?>
												</div>
											</div>
											<?php if(false): ?>
											<div class="col-12">
												<div class="text-center ">
													<p class="mb-i0"><?php echo e(__('auth.dont_have_account')); ?>

														<?php if (isset($component)) { $__componentOriginal08be1f47856809f4c6eda68811b93273 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal08be1f47856809f4c6eda68811b93273 = $attributes; } ?>
<?php $component = App\View\Components\AnchorTag::resolve(['href' => ''.e(route('register')).'','text' => 'Sign up here'] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('anchor-tag'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(App\View\Components\AnchorTag::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal08be1f47856809f4c6eda68811b93273)): ?>
<?php $attributes = $__attributesOriginal08be1f47856809f4c6eda68811b93273; ?>
<?php unset($__attributesOriginal08be1f47856809f4c6eda68811b93273); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal08be1f47856809f4c6eda68811b93273)): ?>
<?php $component = $__componentOriginal08be1f47856809f4c6eda68811b93273; ?>
<?php unset($__componentOriginal08be1f47856809f4c6eda68811b93273); ?>
<?php endif; ?>
													</p>
												</div>
											</div>
											<?php endif; ?>

											<div class="col-12">
												<div class="text-center ">
													<?php if (isset($component)) { $__componentOriginal7721ec5e1620444c27a724578d68d165 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7721ec5e1620444c27a724578d68d165 = $attributes; } ?>
<?php $component = App\View\Components\FlagToggle::resolve(['justLinks' => 'true'] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('flag-toggle'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(App\View\Components\FlagToggle::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7721ec5e1620444c27a724578d68d165)): ?>
<?php $attributes = $__attributesOriginal7721ec5e1620444c27a724578d68d165; ?>
<?php unset($__attributesOriginal7721ec5e1620444c27a724578d68d165); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7721ec5e1620444c27a724578d68d165)): ?>
<?php $component = $__componentOriginal7721ec5e1620444c27a724578d68d165; ?>
<?php unset($__componentOriginal7721ec5e1620444c27a724578d68d165); ?>
<?php endif; ?>
												</div>
											</div>

                                            <?php
                                                $appVersion = getAppVersion();
                                                $dbVersion = getDatabaseMigrationAppVersion();
                                            ?>

											<div class="text-center">
												<span>Version: <?php echo e($appVersion); ?></span>
											</div>


                                            <?php if($appVersion != $dbVersion): ?>
                                            <div class="alert alert-danger border-0 bg-danger alert-dismissible fade show">
                                                <div class="text-white">
                                                    Version Mismatch!!<br>
                                                    <small>
                                                        App Version: <?php echo e($appVersion); ?>,
                                                        Database Version: <?php echo e($dbVersion); ?>

                                                    </small>
                                                </div>
                                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                            </div>
                                            <?php endif; ?>


											<?php echo $__env->make('auth.demo-login', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

										</form>
									</div>

								</div>
							</div>
						</div>
					</div>

				</div>
				<!--end row-->
			</div>
		</div>
	</div>
	<!--end wrapper-->

<?php $__env->stopSection(); ?>

<?php $__env->startSection('js'); ?>
<!-- Login page -->
<script src="custom/js/login.js"></script>
<?php if(config('demo.enabled')): ?>
<script src="custom/js/demo-login.js"></script>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.guest', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\faster_system\resources\views/auth/login.blade.php ENDPATH**/ ?>