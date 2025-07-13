<?php require APPROOT . '/app/views/layouts/header.php'; ?>

<div class="container">
    <h1 class="text-center my-lg-4 my-sm-1"><?php echo htmlspecialchars($data['accountInfo']->account_name); ?></h1>

    <div class="row">
        <div class="col-sm-12 col-md-8 col-lg-6 mb-3">
            <div class="card h-100 shadow-sm">
                <div class="card-header">
                    <h5 class="text-center mb-0 py-1">Account Info</h5>
                </div>
                <div class="card-body">
                    <table class="table my-1 align-middle">
                        <tr>
                            <th class="text-end border-0">Account Name: </th>
                            <td class="border-0"><input id="accountName" type="text" class="form-control" value="<?php echo htmlspecialchars($data['accountInfo']->account_name); ?>" data-initial="<?php echo htmlspecialchars($data['accountInfo']->account_name); ?>"></td>
                        </tr>
                        <tr>
                            <th class="text-end border-0">Default Currency: </th>
                            <td class="border-0"><input id="defaultCurrency" type="text" class="form-control" value="<?php echo htmlspecialchars($data['accountInfo']->currency); ?>" data-initial="<?php echo htmlspecialchars($data['accountInfo']->currency); ?>" oninput="this.value = this.value.toUpperCase();"></td>
                        </tr>
                        <tr>
                            <th class="text-end border-0">Created by: </th>
                            <td class="border-0"><?php echo htmlspecialchars($data['accountInfo']->fname . " " . $data['accountInfo']->lname); ?></td>
                        </tr>
                        <tr>
                            <th class="text-end border-0">Created at: </th>
                            <td class="border-0"><?php echo htmlspecialchars($data['accountInfo']->created_at); ?></td>
                        </tr>
                    </table>
                </div>
                <div class="d-flex card-footer">
                    <button id="deleteAccount" class="btn btn-danger m-auto" type="button">Delete Account<i class="bi bi-trash3 ms-2"></i></button>
                    <button id="updateAccountInfo" class="btn btn-success m-auto d-none" type="button">Save Changes<i class="bi bi-check-lg ms-2"></i></button>
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-8 col-lg-6 mb-3">
            <div class="card h-100 shadow-sm">
                <div class="card-header">
                    <h5 class="text-center mb-0 py-1">Latest Receipts</h5>
                </div>
                <div class="card-body">
                    <table class="table my-1 align-middle">
                        <thead>
                            <tr>
                                <th scope="col">Location</th>
                                <th scope="col">Created At</th>
                                <th scope="col">Payer</th>
                                <th scope="col">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['latestReceipts'] as $receipt): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($receipt->location); ?></td>
                                    <td><?php echo htmlspecialchars($receipt->created_at); ?></td>
                                    <td><?php echo htmlspecialchars($data['accountUsersMap'][$receipt->payer_id]->fname . " " . $data['accountUsersMap'][$receipt->payer_id]->lname); ?></td>
                                    <td><?php echo number_format((float)$receipt->total_amount, 2, '.', '') . " " . htmlspecialchars($receipt->currency); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer d-flex">
                    <a href="<?php echo URLROOT; ?>/receipts/index/<?php echo $data['accountInfo']->id; ?>" class="btn btn-primary m-auto">View All Receipts<i class="bi bi-eyeglasses ms-2"></i></a>
                    <a href="<?php echo URLROOT; ?>/receipts/add/<?php echo $data['accountInfo']->id; ?>" class="btn btn-success m-auto">Add Receipt<i class="bi bi-plus-lg ms-2"></i></a>
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-8 col-lg-6 mb-3">
            <div class="card h-100 shadow-sm">
                <div class="card-header">
                    <h5 class="text-center mb-0 py-1">Account Statistics</h5>
                </div>
                <?php if (!empty($data['accountUsersMap'])): ?>
                    <div class="table-responsive">
                        <table class="table text-center table-striped my-1 align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th scope="col" style="min-width: 150px;"> Paid By →
                                        <hr style="margin: 0px;">Paid For ↓
                                    </th>
                                    <?php foreach ($data['accountUsersMap'] as $payer_id => $payer_info): ?>
                                        <th scope="col" style="min-width: 120px;">
                                            <?php echo htmlspecialchars($payer_info->fname . " " . $payer_info->lname); ?>
                                        </th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                foreach ($data['beneficiarySubsets'] as $signature => $subsetIdArray):
                                ?>
                                    <tr>
                                        <th scope="row">
                                            <?php
                                            if (count($subsetIdArray) == count($data['accountUsersMap'])) {
                                                echo "Everyone";
                                            } else {
                                                $names = [];
                                                foreach ($subsetIdArray as $userId) {
                                                    $user_info = $data['accountUsersMap'][$userId] ?? (object)['fname' => 'Unknown', 'lname' => 'User'];
                                                    $names[] = htmlspecialchars($user_info->fname . " " . $user_info->lname);
                                                }
                                                echo implode(' and ', $names);
                                            }
                                            ?>
                                        </th>
                                        <?php
                                        foreach ($data['accountUsersMap'] as $payer_id => $payer_info):
                                            $cell_output_parts = [];
                                            if (isset($data['statistics'][$payer_id][$signature])) {
                                                foreach ($data['statistics'][$payer_id][$signature] as $currency => $amount) {
                                                    $formatted_amount = number_format((float)$amount, 2, '.', '');
                                                    $cell_output_parts[] = $formatted_amount . " " . htmlspecialchars($currency);
                                                }
                                            }
                                            $cell_content = !empty($cell_output_parts) ? implode('<br>', $cell_output_parts) : '0.00';
                                        ?>
                                            <td><?php echo $cell_content; ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="card-body text-center">
                        <p class="text-muted">No users found in this account.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-sm-12 col-md-4 col-lg-3 mb-3">
            <div class="card h-100 shadow-sm card-light">
                <div class="card-header">
                    <h5 class="text-center mb-0 py-1">Amount Paid for Others</h5>
                </div>
                <div class="card-body px-0">
                    <?php if (!empty($data['accountUsersMap'])): ?>
                        <table class="table table-striped text-center align-middle mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th scope="col">User</th>
                                    <th scope="col">Total Amount Paid For Others</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['accountUsersMap'] as $payer_id => $payer_info): ?>
                                    <tr>
                                        <th scope="row"> <?php echo htmlspecialchars($payer_info->fname . " " . $payer_info->lname); ?>
                                        </th>
                                        <td>
                                            <?php
                                            $output_parts = [];
                                            if (isset($data['paidForOthersStats'][$payer_id])) {
                                                foreach ($data['paidForOthersStats'][$payer_id] as $currency => $amount) {
                                                    $formatted_amount = number_format((float)$amount, 2, '.', '');
                                                    $output_parts[] = $formatted_amount . " " . htmlspecialchars($currency);
                                                }
                                            }
                                            echo !empty($output_parts) ? implode(' + ', $output_parts) : '0.00';
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="card-body text-center">
                            <p class="text-muted">No users found in this account.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-md-4 col-lg-3 mb-3">
            <div class="card h-100 shadow-sm">
                <div class="card-header">
                    <h5 class="text-center mb-0 py-1">Manage Users</h5>
                </div>
                <div class="card-body">
                    <table class="table my-1 align-middle text-center">
                        <tr>
                            <th>User</th>
                            <th>Action</th>
                        </tr>
                        <?php foreach ($data['accountUsersList'] as $accountUser): ?>
                            <tr>
                                <td class="text-start"><?php echo htmlspecialchars($accountUser->fname . " " . $accountUser->lname); ?></td>
                                <?php if ($accountUser->id != $data['accountInfo']->created_by): ?>
                                    <td class=""><button class="btn btn-danger btn-sm remove-user-btn" type="button" data-user-id="<?php echo $accountUser->id; ?>">Remove</button></td>
                                <?php else: ?>
                                    <td>Owner</td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <div class="card-footer d-flex">
                    <button type="button" class="btn btn-success m-auto w-50" data-bs-toggle="modal" data-bs-target="#addUserModal">Add User<i class="bi bi-person-fill-add ms-2"></i></button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addUserModalLabel">Add User to Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="usernameSearch" class="form-label">Search User by Username:</label>
                    <input type="text" class="form-control" id="usernameSearch" placeholder="Enter username">
                    <div id="usernameSearchFeedback" class="form-text"></div>
                </div>
                <div id="userSearchResults" class="list-group mb-3">
                    <!-- Search results will be appended here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const inputs = document.querySelectorAll('.form-control');
        const saveBtn = document.getElementById('updateAccountInfo');

        function checkForChanges() {
            let changed = false;
            inputs.forEach(input => {
                if (input.value !== input.getAttribute('data-initial')) {
                    changed = true;
                }
            });

            if (changed) {
                saveBtn.classList.remove("d-none");
            } else {
                saveBtn.classList.add("d-none");
            }
        }

        inputs.forEach(input => {
            input.addEventListener('input', checkForChanges);
        });

        saveBtn.onclick = function() {
            const newNameInput = document.getElementById("accountName");
            const newCurrencyInput = document.getElementById("defaultCurrency");
            const newName = newNameInput.value;
            const newCurrency = newCurrencyInput.value;
            const accountId = <?php echo $data['accountInfo']->id; ?>;

            if (!newName || newName.length > 50) {
                alert('Account name cannot be empty and must be 50 characters or less.');
                return;
            }
            if (!newCurrency) {
                alert('Currency cannot be empty.');
                return;
            }

            fetch("<?php echo URLROOT; ?>/accounts/updateAccountInfo", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        account_id: accountId,
                        new_name: newName,
                        new_currency: newCurrency
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert("Update failed: " + data.message);
                    }
                });
        }

        const deleteBtn = document.getElementById('deleteAccount');
        deleteBtn.onclick = function() {
            if (confirm('Are you sure you want to delete this account? This action cannot be undone.')) {
                const accountId = <?php echo $data['accountInfo']->id; ?>;
                fetch("<?php echo URLROOT; ?>/accounts/deleteAccount", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json"
                        },
                        body: JSON.stringify({
                            account_id: accountId
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            window.location.href = "<?php echo URLROOT; ?>/accounts";
                        } else {
                            alert("Delete failed: " + data.message);
                        }
                    });
            }
        }
    });

    // Add User Modal Logic
    const addUserModal = document.getElementById('addUserModal');
    const usernameSearchInput = document.getElementById('usernameSearch');
    const usernameSearchFeedback = document.getElementById('usernameSearchFeedback');
    const userSearchResultsDiv = document.getElementById('userSearchResults');
    const currentAccountId = <?php echo $data['accountInfo']->id; ?>;

    let searchTimeout;

    usernameSearchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const searchTerm = this.value.trim();

        if (searchTerm.length < 3) {
            userSearchResultsDiv.innerHTML = '';
            usernameSearchFeedback.textContent = 'Enter at least 3 characters to search.';
            return;
        }

        usernameSearchFeedback.textContent = 'Searching...';
        userSearchResultsDiv.innerHTML = '';

        searchTimeout = setTimeout(() => {
            fetch(`<?php echo URLROOT; ?>/accounts/searchUsers?term=${searchTerm}&account_id=${currentAccountId}`)
                .then(response => response.json())
                .then(users => {
                    userSearchResultsDiv.innerHTML = '';
                    if (users.length > 0) {
                        usernameSearchFeedback.textContent = '';
                        users.forEach(user => {
                            const userItem = document.createElement('button');
                            userItem.type = 'button';
                            userItem.className = 'list-group-item list-group-item-action';
                            userItem.innerHTML = `${user.username} (${user.fname} ${user.lname}) <i class="bi bi-plus-circle ms-2"></i>`;
                            userItem.dataset.userId = user.id;
                            userItem.dataset.username = user.username;
                            userItem.addEventListener('click', function() {
                                addUserToAccount(this.dataset.userId, this.dataset.username);
                            });
                            userSearchResultsDiv.appendChild(userItem);
                        });
                    } else {
                        usernameSearchFeedback.textContent = 'No users found.';
                    }
                })
                .catch(error => {
                    console.error('Error searching users:', error);
                    usernameSearchFeedback.textContent = 'Error searching users.';
                });
        }, 500);
    });

    function addUserToAccount(userId, username) {
        fetch("<?php echo URLROOT; ?>/accounts/addUserToAccount", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    account_id: currentAccountId,
                    user_id: userId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(`Failed to add user: ${data.message}`);
                }
            })
            .catch(error => {
                console.error('Error adding user:', error);
                alert('An error occurred while adding user.');
            });
    }

    // Remove User Logic
    document.querySelectorAll('.remove-user-btn').forEach(button => {
        button.addEventListener('click', function() {
            const userIdToRemove = this.dataset.userId;
            const userNameToRemove = this.closest('tr').querySelector('td').textContent.trim(); // Get username from table cell

            if (confirm(`Are you sure you want to remove ${userNameToRemove} from this account?`)) {
                fetch("<?php echo URLROOT; ?>/accounts/removeUserFromAccount", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json"
                        },
                        body: JSON.stringify({
                            account_id: currentAccountId,
                            user_id: userIdToRemove
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(`User ${userNameToRemove} removed successfully.`);
                            window.location.reload();
                        } else {
                            alert(`Failed to remove user: ${data.message}`);
                        }
                    })
                    .catch(error => {
                        console.error('Error removing user:', error);
                        alert('An error occurred while removing user.');
                    });
            }
        });
    });
</script>
<?php require APPROOT . '/app/views/layouts/footer.php'; ?>