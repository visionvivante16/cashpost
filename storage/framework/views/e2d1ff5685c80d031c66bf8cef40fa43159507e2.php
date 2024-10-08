<!doctype html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
	<meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
	<meta name="app-url" content="<?php echo e(env('APP_URL')); ?>">

	<!-- Required meta tags -->
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title><?php echo e(config('app.name', 'Active Workdesk')); ?></title>

	<!-- google font -->
	<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700">

	<!-- aiz core css -->
	<link rel="stylesheet" href="<?php echo e(my_asset('assets/common/css/vendors.css')); ?>">
	<link rel="stylesheet" href="<?php echo e(my_asset('assets/common/css/aiz-core.css')); ?>">

	<script>
    	var AIZ = AIZ || {};
	</script>
</head>
<body>
    <div class="aiz-main-wrapper d-flex">

		<div class="flex-grow-1">
            <?php echo $__env->yieldContent('content'); ?>
		</div>

	</div><!-- .aiz-main-wrapper -->
	<script src="<?php echo e(my_asset('assets/common/js/vendors.js')); ?>" ></script>
	<script src="<?php echo e(my_asset('assets/common/js/aiz-core.js')); ?>" ></script>

    <?php echo $__env->yieldContent('script'); ?>

    <script type="text/javascript">
    <?php $__currentLoopData = session('flash_notification', collect())->toArray(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $message): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        AIZ.plugins.notify('<?php echo e($message['level']); ?>', '<?php echo e($message['message']); ?>');
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </script>
</body>
</html>
<?php /**PATH /www/wwwroot/public_html/cashpost.visionvivante.com/resources/views/admin/default/layouts/blank.blade.php ENDPATH**/ ?>