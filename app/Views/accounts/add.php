<?php require APPROOT . '/app/views/layouts/header.php'; ?>
    <a href="<?php echo URLROOT; ?>/accounts" class="btn btn-light"><i class="fa fa-backward"></i> Back</a>
    <div class="card card-body bg-light mt-5">
        <h2>Add Account</h2>
        <p>Create a new account</p>
        <form action="<?php echo URLROOT; ?>/accounts/add" method="post">
            <div class="form-group">
                <label for="account_name">Account Name: <sup>*</sup></label>
                <input type="text" name="account_name" class="form-control form-control-lg <?php echo (!empty($data['account_name_err'])) ? 'is-invalid' : ''; ?>" value="<?php echo $data['account_name']; ?>">
                <span class="invalid-feedback"><?php echo $data['account_name_err']; ?></span>
            </div>
            <div class="form-group">
                <label for="currency">Currency: <sup>*</sup></label>
                <input type="text" name="currency" class="form-control form-control-lg <?php echo (!empty($data['currency_err'])) ? 'is-invalid' : ''; ?>" value="<?php echo $data['currency']; ?>">
                <span class="invalid-feedback"><?php echo $data['currency_err']; ?></span>
            </div>
            <input type="submit" class="btn btn-success" value="Submit">
        </form>
    </div>
<?php require APPROOT . '/app/views/layouts/footer.php'; ?>
