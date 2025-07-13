<?php
require_once APPROOT . '/app/helpers/session_helper.php';
require_once APPROOT . '/app/helpers/breadcrumb_helper.php';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITENAME; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/dark.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.2/css/responsive.bootstrap5.min.css">
    <link rel="stylesheet" href="<?php echo URLROOT; ?>/css/style.css">
</head>

<body>

    <?php if (isLoggedIn()) : ?>
        <?php require APPROOT . '/app/views/partials/navbar.php'; ?>
    <?php endif; ?>

    <div class="container">
        <?php if (isset($data['breadcrumbs']) && is_array($data['breadcrumbs'])) : ?>
            <?php echo generate_breadcrumbs($data['breadcrumbs']); ?>
        <?php endif; ?>
        <?php flash('post_message'); ?>
    </div>