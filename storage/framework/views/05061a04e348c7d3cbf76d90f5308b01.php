<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
<title>Sign in · <?php echo e(config('app.name')); ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"><?php if(file_exists(public_path('build/manifest.json'))): ?><?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css','resources/js/app.js']); ?><?php else: ?><link href="<?php echo e(asset('css/app.css')); ?>" rel="stylesheet"><?php endif; ?>
</head>
<body>
<div class="login-page" style="--blue: <?php echo e($systemBranding['primary'] ?? '#315f9f'); ?>; --green: <?php echo e($systemBranding['accent'] ?? '#0caa6a'); ?>;">
 <section class="login-aside">
  <div class="login-brand"><?php if(!empty($systemBranding['logo_url'])): ?><div class="login-brand-mark login-brand-mark--image"><img src="<?php echo e($systemBranding['logo_url']); ?>" alt="<?php echo e($systemBranding['short_name'] ?? 'System'); ?>"></div><?php else: ?><div class="login-brand-mark" style="--blue: <?php echo e($systemBranding['primary'] ?? '#315f9f'); ?>;"><?php echo e(strtoupper(substr($systemBranding['short_name'] ?? 'IT', 0, 3))); ?></div><?php endif; ?><div><strong><?php echo e($systemBranding['name'] ?? 'IT Inventory'); ?></strong><span>Management Portal</span></div></div>
  <div class="login-message"><div class="eyebrow">ASSET ACCOUNTABILITY</div><h1>A clear view of every IT asset.</h1><p>Manage equipment, assignments, maintenance, consumables, and inventory accountability in one controlled workspace.</p><div class="login-feature"><i class="bi bi-shield-check"></i><span>Role-based access and recorded activity</span></div><div class="login-feature"><i class="bi bi-upc-scan"></i><span>Traceable asset records and QR identification</span></div></div>
  <div class="login-foot">© <?php echo e(date('Y')); ?> <?php echo e($systemBranding['name'] ?? 'IT Inventory Management'); ?></div>
 </section>
 <main class="login-main"><div class="login-card">
  <div class="eyebrow mb-2"></div><h2>Sign in</h2>
  <?php if($errors->any()): ?><div class="alert alert-danger py-2 small"><?php echo e($errors->first()); ?></div><?php endif; ?>
  <form method="POST" action="<?php echo e(route('login.store')); ?>"><?php echo csrf_field(); ?>
   <div class="mb-3"><label class="form-label" for="email">Email address</label><input id="email" type="email" name="email" class="form-control" value="<?php echo e(old('email')); ?>" placeholder="name@organization.gov" autocomplete="username" required autofocus></div>
   <div class="mb-3"><label class="form-label" for="password">Password</label><input id="password" type="password" name="password" class="form-control" placeholder="Enter your password" autocomplete="current-password" required></div>
   <div class="d-flex align-items-center justify-content-between mb-3"><label class="form-check small mb-0"><input class="form-check-input" type="checkbox" name="remember" value="1"><span class="form-check-label">Remember me</span></label><span class="small text-secondary">Authorized users only</span></div>
   <button class="btn btn-primary login-submit" type="submit">Sign in <i class="bi bi-arrow-right ms-2"></i></button>
  </form>
  <div class="login-wordmark">Developed by Bobby V Domdom Jr</div>
 </div></main>
</div>
</body></html>
<?php /**PATH C:\laragon\www\it-inventory\resources\views/auth/login.blade.php ENDPATH**/ ?>