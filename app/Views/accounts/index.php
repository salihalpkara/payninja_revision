<?php require APPROOT . '/app/views/layouts/header.php'; ?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center my-lg-4 my-sm-1">
        <h1 class="mb-0">Accounts</h1>
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addAccountModal">
            <i class="bi bi-plus-lg me-2"></i>Create New Account
        </button>
    </div>

    <div class="row justify-content-center">
        <?php foreach ($data['accounts'] as $account): ?>
            <div class="col-sm-12 col-md-8 col-lg-6 mb-3">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title text-center"><?php echo htmlspecialchars($account['account_name']); ?></h5>
                    </div>
                    <div class="card-body text-center">
                        <?php if (!empty($account['users'])): ?>
                            <p><strong>Users:</strong></p>
                            <ul class="list-unstyled">
                                <?php foreach ($account['users'] as $user): ?>
                                    <li><?php echo htmlspecialchars($user['fname']) . " " . htmlspecialchars($user['lname']); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p>No users found for this account.</p>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer d-flex justify-content-between">
                        <button class="btn btn-danger" onclick="deleteAccount(<?php echo $account['id']; ?>)"><i class="bi bi-trash me-2"></i>Delete Account</button>
                        <div>
                            <a href="<?php echo URLROOT; ?>/accounts/show/<?php echo $account['id']; ?>" class="btn btn-primary"><i class="bi bi-eyeglasses me-2"></i>View Account</a>
                            <a href="<?php echo URLROOT; ?>/receipts/add/<?php echo $account['id']; ?>" class="btn btn-success ms-2"><i class="bi bi-plus-lg me-2"></i>Add Receipt</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function deleteAccount(accountId) {
    if (confirm('Are you sure you want to delete this account? This action cannot be undone.')) {
        fetch('<?php echo URLROOT; ?>/accounts/deleteAccount', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ account_id: accountId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the account.');
        });
    }
}
</script>

<!-- Add Account Modal -->
<div class="modal fade" id="addAccountModal" tabindex="-1" aria-labelledby="addAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addAccountModalLabel">Create New Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="<?php echo URLROOT; ?>/accounts/add" method="post">
                    <div class="mb-3">
                        <label for="account_name" class="form-label">Account Name: <sup>*</sup></label>
                        <input type="text" name="account_name" class="form-control" required maxlength="50">
                    </div>
                    <div class="mb-3">
                        <label for="currency" class="form-label">Default Currency: <sup>*</sup></label>
                        <input type="text" name="currency" class="form-control" required oninput="this.value = this.value.toUpperCase();">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Create Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require APPROOT . '/app/views/layouts/footer.php'; ?>