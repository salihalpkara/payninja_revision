<?php require APPROOT . '/app/views/layouts/header.php'; ?>

<div class="container pb-5">
    <div class="row mt-3 justify-content-start">
        <div class="col">
            <a href="<?php echo URLROOT; ?>/receipts/index/<?php echo $data['account_id']; ?>" class="btn btn-secondary me-2"><i class="bi bi-arrow-left me-2"></i>Back to Receipts</a>
        </div>
    </div>
    <div class="row">
        <h1 class="m-1 text-center"><?php echo $data['page_title']; ?></h1>
    </div>
    <div class="text-center">
    </div>

    <form action="<?php echo URLROOT; ?>/receipts/edit/<?php echo $data['receipt_id']; ?>" method="post" id="receiptForm">
        <input type="hidden" name="receipt_id" value="<?php echo htmlspecialchars($data['receipt_id']); ?>">
        <input type="hidden" name="account_id" value="<?php echo htmlspecialchars($data['account_id']); ?>">

        <div class="row">
            <div class="col-sm-12 col-md-8 col-lg-6 mx-auto">
                <h3 class="text-center my-lg-4 my-sm-1">Receipt Details</h3>

                <div class="mb-3">
                    <label for="location" class="form-label">Location</label>
                    <input class="form-control <?php echo (!empty($data['location_err'])) ? 'is-invalid' : ''; ?>" list="locations" id="location" placeholder="Where was the shopping done?" name="location" required value="<?php echo htmlspecialchars($data['location']); ?>">
                    <datalist id="locations">
                        <?php foreach ($data['locations'] as $loc) : ?>
                            <option value="<?php echo htmlspecialchars($loc->location); ?>">
                        <?php endforeach; ?>
                    </datalist>
                    <span class="invalid-feedback"><?php echo $data['location_err']; ?></span>
                </div>

                <div class="mb-3">
                    <label for="receipt_date_time" class="form-label">Date</label>
                    <input type="datetime-local" class="form-control <?php echo (!empty($data['receipt_date_time_err'])) ? 'is-invalid' : ''; ?>" id="receipt_date_time" name="receipt_date_time" value="<?php echo htmlspecialchars($data['receipt_date_time']); ?>" required>
                    <span class="invalid-feedback"><?php echo $data['receipt_date_time_err']; ?></span>
                </div>

                <div class="mb-3">
                    <label for="payer_id" class="form-label">Payer</label>
                    <select class="form-select" id="payer_id" name="payer_id">
                        <?php foreach ($data['users_in_account'] as $user) : ?>
                            <option value="<?php echo htmlspecialchars($user->id); ?>" <?php echo ($user->id == $data['payer_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user->fname . ' ' . $user->lname); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <table class="table table-borderless">
                        <thead>
                            <tr>
                                <th scope="col" class="ps-0">Total Amount</th>
                                <th scope="col">Currency</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="ps-0">
                                    <div class="input-group">
                                        <input type="number" step=".01" class="form-control <?php echo (!empty($data['total_amount_err'])) ? 'is-invalid' : ''; ?>" id="totalAmount" name="total_amount" required placeholder="Enter total amount from receipt" oninput="calculateTotalAmount();" value="<?php echo htmlspecialchars($data['total_amount']); ?>">
                                        <div class="input-group-text" id="calculatedAmount">Calculated Amount: -,--</div>
                                    </div>
                                    <div class="form-text" id="amountControl"></div>
                                    <span class="invalid-feedback"><?php echo $data['total_amount_err']; ?></span>
                                </td>
                                <td width="80">
                                    <div class="mb-0">
                                        <input oninput="this.value = this.value.toUpperCase(); updateItemCurrencies(this.value);" type="text" class="form-control text-center" id="currency" name="currency" list="currenciesDatalist" required value="<?php echo htmlspecialchars($data['currency']); ?>">
                                        <datalist id="currenciesDatalist">
                                            <?php foreach ($data['currencies'] as $currencyOption) : ?>
                                                <option value="<?php echo htmlspecialchars($currencyOption); ?>"><?php echo htmlspecialchars($currencyOption); ?></option>
                                            <?php endforeach; ?>
                                        </datalist>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mb-3">
                    <label for="receipt_note" class="form-label">Receipt Note (Optional)</label>
                    <textarea class="form-control" id="receipt_note" name="receipt_note" rows="3" placeholder="Enter any notes for this receipt..."><?php echo htmlspecialchars($data['receipt_note']); ?></textarea>
                </div>
            </div>

            <div class="col-sm-12 col-md-8 col-lg-6 mx-auto">
                <h3 class="text-center my-lg-4 my-sm-1">Items</h3>
                <div class="accordion mb-3" id="itemAccordion">
                </div>
                <div class="text-center mb-3">
                    <button type="button" class="btn btn-primary" onclick="addItem();"><i class="bi bi-plus-lg me-2"></i>Add Another Item</button>
                </div>
                <div class="text-center d-flex justify-content-end">
                    <button type="submit" class="btn btn-success" id="createReceiptBtn" disabled><i class="bi bi-save me-2"></i>Save Changes</button>
                </div>
            </div>
        </div>
    </form>
</div>

<div class="modal fade" id="confirmReceiptModal" tabindex="-1" aria-labelledby="confirmReceiptModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmReceiptModalLabel">Confirm Changes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to save these changes?</p>
                <div id="receiptPreview" class="receipt-preview"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmYes">Yes, Save</button>
            </div>
        </div>
    </div>
</div>

<script>
    const R_ACCOUNT_ID = <?php echo htmlspecialchars($data['account_id']); ?>;
    const R_CURRENT_USER_ID = <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'null'; ?>;
    let R_DEFAULT_CURRENCY = "<?php echo htmlspecialchars($data['currency']); ?>";
    const R_ACCOUNT_USERS = <?php echo json_encode($data['users_in_account']); ?>;
    const R_EXISTING_ITEMS = <?php echo json_encode($data['items']); ?>;

    const itemAccordion = document.getElementById("itemAccordion");
    let itemIndex = 0;
    let readyToSubmitForm = false;

    function formatCurrency(value, currencyCode) {
        try {
            return new Intl.NumberFormat(undefined, {
                style: 'currency',
                currency: currencyCode,
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(value);
        } catch (e) {
            return `${parseFloat(value).toFixed(2)} ${currencyCode}`;
        }
    }

    function formatReceiptDate(dateString) {
        if (!dateString) return 'N/A';
        try {
            const dateObj = new Date(dateString);
            return dateObj.toLocaleString(undefined, {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch (e) {
            return dateString;
        }
    }

    function populateReceiptPreview() {
        const previewDiv = document.getElementById('receiptPreview');
        const currentCurrency = document.getElementById('currency').value.toUpperCase() || R_DEFAULT_CURRENCY;
        let html = '';
        html += `<h5 class="preview-section-title"><i class="bi bi-geo-alt-fill"></i> Location:</h5><p class="preview-data">${document.getElementById('location').value || 'N/A'}</p>`;
        html += `<h5 class="preview-section-title"><i class="bi bi-calendar-event-fill"></i> Date & Time:</h5><p class="preview-data">${formatReceiptDate(document.getElementById('receipt_date_time').value)}</p>`;
        html += `<h5 class="preview-section-title"><i class="bi bi-person-fill"></i> Payer:</h5><p class="preview-data">${document.getElementById('payer_id').selectedOptions[0].text || 'N/A'}</p>`;
        html += `<hr class="dashed">`;
        html += `<div class="section-header">ITEMS</div>`;
        html += `<div class="item-section">`;
        const items = document.querySelectorAll('#itemAccordion .accordion-item');
        let calculatedSubtotalForPreview = 0;
        if (items.length === 0) {
            html += `<p class="text-center text-muted">No items added.</p>`;
        }
        items.forEach((item, idx) => {
            const itemNameEl = item.querySelector(`input[name="item_name[${idx}]"]`);
            const quantityEl = item.querySelector(`input[name="amount[${idx}]"]`);
            const priceEl = item.querySelector(`input[name="item_price[${idx}]"]`);
            const itemName = itemNameEl ? itemNameEl.value.trim() || 'Unnamed Item' : 'Unknown Item';
            const quantity = quantityEl ? parseInt(quantityEl.value) : 0;
            const price = priceEl ? parseFloat(priceEl.value) : 0;
            const itemTotal = quantity * price;
            calculatedSubtotalForPreview += itemTotal;
            html += `<div class="item-line">`;
            html += `  <span class="item-name-qty">${quantity} x ${itemName}</span>`;
            html += `  <span class="item-price-total">${formatCurrency(itemTotal, currentCurrency)}</span>`;
            html += `</div>`;
            const boughtForCheckboxes = item.querySelectorAll(`input[name="bought_for[${idx}][]"]:checked`);
            if (boughtForCheckboxes.length > 0) {
                let boughtForNames = Array.from(boughtForCheckboxes).map(cb => {
                    const userId = cb.value;
                    const user = R_ACCOUNT_USERS.find(u => u.id.toString() === userId.toString());
                    return user ? `${user.fname} ${user.lname}` : `User ID: ${userId}`;
                }).join(', ');
                html += `<div class="bought-for-list">&hookrightarrow; For: ${boughtForNames}</div>`;
            }
        });
        html += `</div>`;
        html += `<hr class="dashed">`;
        const totalAmount = parseFloat(document.getElementById('totalAmount').value) || 0;
        html += `<div class="totals-line">`;
        html += `  <span>TOTAL:</span>`;
        html += `  <span>${formatCurrency(totalAmount, currentCurrency)}</span>`;
        html += `</div>`;
        if (items.length > 0 && Math.abs(calculatedSubtotalForPreview - totalAmount) > 0.005) {
            html += `<p class="text-danger text-center small mt-2">Note: Sum of item totals (${formatCurrency(calculatedSubtotalForPreview, currentCurrency)}) does not match entered Total Amount.</p>`;
        }
        const receiptNote = document.getElementById('receipt_note').value;
        if (receiptNote.trim() !== '') {
            html += `<hr class="dashed">`;
            html += `<h5 class="preview-section-title"><i class="bi bi-sticky-fill"></i> Note:</h5><p class="preview-data" style="white-space: pre-wrap;">${receiptNote}</p>`;
        }
        html += `<p class="footer-note">Thank you!</p>`;
        previewDiv.innerHTML = html;
    }

    function updateItemCurrencies(newCurrency) {
        R_DEFAULT_CURRENCY = newCurrency.toUpperCase();
        document.querySelectorAll(".itemCurrency").forEach(el => el.textContent = R_DEFAULT_CURRENCY);
        document.querySelectorAll('#itemAccordion .accordion-item').forEach(item => {
            const nameInput = item.querySelector('input[id^="item_name_"]');
            if (nameInput) updateAccordionHeader(nameInput);
        });
        if (document.getElementById('confirmReceiptModal')?.classList.contains('show')) {
            populateReceiptPreview();
        }
    }

    function attachGlobalEventListeners() {
        document.getElementById("currency")?.addEventListener("input", function() {
            updateItemCurrencies(this.value);
        });
    }

    function removeItem(buttonElement) {
        const item = buttonElement.closest(".accordion-item");
        if (item) {
            const existingItemIdInput = item.querySelector('input[name^="item_id["]');
            if (existingItemIdInput && existingItemIdInput.value !== 'new') {
                const deletedInput = document.createElement('input');
                deletedInput.type = 'hidden';
                deletedInput.name = 'deleted_items[]';
                deletedInput.value = existingItemIdInput.value;
                document.getElementById('receiptForm').appendChild(deletedInput);
            }
            item.remove();
            if (itemAccordion.querySelectorAll(".accordion-item").length === 0) addItem();
            calculateTotalAmount();
        }
    }

    function updateAccordionHeader(nameInputElement) {
        const accordionItem = nameInputElement.closest('.accordion-item');
        if (!accordionItem) return;
        const headerSpan = accordionItem.querySelector('.accordion-header .itemNameText');
        if (!headerSpan) return;

        const idParts = nameInputElement.id.split('_');
        const currentItemUniqueId = idParts.slice(2).join('_');

        const itemName = nameInputElement.value.trim();

        const priceInput = accordionItem.querySelector(`#item_price_${currentItemUniqueId}`);
        let priceText = "";
        if (priceInput && priceInput.value.trim() !== "") {
            const priceValue = parseFloat(priceInput.value);
            if (!isNaN(priceValue)) {
                const currentReceiptCurrency = document.getElementById('currency').value.toUpperCase() || R_DEFAULT_CURRENCY;
                priceText = ` | ${formatCurrency(priceValue, currentReceiptCurrency)}`;
            }
        }

        const itemElements = Array.from(itemAccordion.children);
        const currentIndexInUI = itemElements.indexOf(accordionItem);
        const displayIndex = currentIndexInUI + 1;
        const placeholderBaseText = `Item ${displayIndex}`;

        if (itemName) {
            headerSpan.textContent = itemName + priceText;
        } else {
            headerSpan.textContent = placeholderBaseText + priceText;
        }
    }

    function updateHeaderFromPriceChange(priceInputElement) {
        const accordionItem = priceInputElement.closest('.accordion-item');
        if (!accordionItem) return;

        const idParts = priceInputElement.id.split('_');
        const currentItemUniqueId = idParts.slice(2).join('_');

        const nameInput = accordionItem.querySelector(`#item_name_${currentItemUniqueId}`);
        if (nameInput) {
            updateAccordionHeader(nameInput);
        }
    }

    function addItem(itemData = null) {
        const allItemCollapses = itemAccordion.querySelectorAll(".accordion-collapse.show");
        allItemCollapses.forEach(collapseEl => {
            const ci = bootstrap.Collapse.getInstance(collapseEl);
            if (ci) ci.hide();
        });
        document.querySelectorAll(".accordion-button:not(.collapsed)").forEach(button => {
            button.classList.add("collapsed");
            button.setAttribute("aria-expanded", "false");
        });

        const newItem = document.createElement("div");
        newItem.className = "accordion-item";
        const currentItemUniqueId = `itemDetail_${itemIndex}`;
        const currentItemDisplayIndex = itemAccordion.children.length + 1;

        const boughtForUserIds = itemData ? (itemData.bought_for_users || []).map(String) : [];
        const isNewItem = !itemData;

        let userListHTML = `<label class="list-group-item"><input class="form-check-input me-1 selectAllUsers" type="checkbox" value="">Everyone</label>`;
        if (R_ACCOUNT_USERS && R_ACCOUNT_USERS.length > 0) {
            R_ACCOUNT_USERS.forEach(user => {
                let isChecked = false;
                if (isNewItem) {
                    isChecked = parseInt(user.id) === parseInt(R_CURRENT_USER_ID);
                } else {
                    isChecked = boughtForUserIds.includes(user.id.toString());
                }
                userListHTML += `<label class='list-group-item'><input class='form-check-input me-1 user-checkbox' type='checkbox' name='bought_for[${itemIndex}][]' value='${user.id}' ${isChecked ? "checked" : ""}>${user.fname} ${user.lname}</label>`;
            });
        }

        const placeholderText = `Item ${currentItemDisplayIndex}`;
        newItem.innerHTML = `
            <h2 class="accordion-header" id="header_${currentItemUniqueId}">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_${currentItemUniqueId}" aria-expanded="true" aria-controls="collapse_${currentItemUniqueId}">
                    
                <div class="d-flex align-items-center justify-content-center gap-2" style="min-height: 2.2em;">
                    <span class="btn btn-danger btn-sm" onclick="removeItem(this); event.stopPropagation();"><i class="bi bi-trash"></i></span>
                    <span class="itemNameText" style="line-height: 1.6;">${placeholderText}</span>
                </div>
                </button>
            </h2>
            <div id="collapse_${currentItemUniqueId}" class="accordion-collapse collapse show" aria-labelledby="header_${currentItemUniqueId}" data-bs-parent="#itemAccordion">
                <div class="accordion-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="item_name_${currentItemUniqueId}" class="form-label">Item Name:</label>
                                <input type="text" class="form-control item-name-input" id="item_name_${currentItemUniqueId}" name="item_name[${itemIndex}]" placeholder="Enter item name" oninput="updateAccordionHeader(this)" required>
                            </div>
                            <div class="row mb-3 align-items-end">
                                <div class="col">
                                    <label for="amount_${currentItemUniqueId}" class="form-label">Quantity:</label>
                                    <div class="input-group">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="decrementAmount(this);"><i class="bi bi-dash-lg"></i></button>
                                        <input type="number" class="form-control text-center flex-grow-0" style="width: 70px;" id="amount_${currentItemUniqueId}" name="amount[${itemIndex}]" value="1" min="1" required oninput="calculateTotalAmount();">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="incrementAmount(this);"><i class="bi bi-plus-lg"></i></button>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                 <div class="col">
                                    <label for="item_price_${currentItemUniqueId}" class="form-label">Price per item:</label>
                                    <div class="input-group">
                                        <span class="input-group-text itemCurrency">${R_DEFAULT_CURRENCY}</span>
                                        <input type="number" step=".01" class="form-control" id="item_price_${currentItemUniqueId}" name="item_price[${itemIndex}]" placeholder="Price" required oninput="updateHeaderFromPriceChange(this); calculateTotalAmount();">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label id="bought_for_label_${currentItemUniqueId}" class="form-label">Bought For:</label>
                            <div class="list-group user-list-group" id="bought_for_group_${currentItemUniqueId}" style="max-height: 200px; overflow-y: auto;" role="group" aria-labelledby="bought_for_label_${currentItemUniqueId}">
                                ${userListHTML}
                            </div>
                            <div id="bought_for_error_${currentItemUniqueId}" class="text-danger mt-1" style="display: none;">Please select at least one person.</div>
                        </div>
                    </div>
                </div>
            </div>`;
        itemAccordion.appendChild(newItem);

        if (itemData) {
            const accordionBody = newItem.querySelector('.accordion-body');
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = `item_id[${itemIndex}]`;
            idInput.value = itemData.id || 'new';
            accordionBody.appendChild(idInput);

            newItem.querySelector(`#item_name_${currentItemUniqueId}`).value = itemData.name || '';
            newItem.querySelector(`#amount_${currentItemUniqueId}`).value = itemData.quantity || 1;
            newItem.querySelector(`#item_price_${currentItemUniqueId}`).value = itemData.price ? parseFloat(itemData.price).toFixed(2) : '';
        }

        const newItemNameInput = newItem.querySelector(`#item_name_${currentItemUniqueId}`);
        if (newItemNameInput) updateAccordionHeader(newItemNameInput);
        const newCollapseElement = newItem.querySelector('.accordion-collapse');
        if (newCollapseElement) new bootstrap.Collapse(newCollapseElement, {
            toggle: false
        });
        attachSelectAllHandlers(newItem);
        itemIndex++;
    }

    function calculateTotalAmount() {
        let total = 0;
        itemAccordion.querySelectorAll('.accordion-item').forEach(item => {
            const priceInput = item.querySelector('input[name^="item_price["]');
            const amountInput = item.querySelector('input[name^="amount["]');
            if (priceInput && amountInput) total += (parseFloat(priceInput.value) || 0) * (parseInt(amountInput.value) || 1);
        });
        document.getElementById('calculatedAmount').textContent = `Calculated: ${formatCurrency(total, document.getElementById('currency').value || R_DEFAULT_CURRENCY)}`;
        const totalAmountInput = document.getElementById('totalAmount');
        const submitButton = document.getElementById('createReceiptBtn');
        const amountControlDiv = document.getElementById('amountControl');
        const enteredTotal = parseFloat(totalAmountInput.value);
        if (totalAmountInput.value.trim() !== "" && Math.abs(total - enteredTotal) < 0.005) {
            totalAmountInput.classList.remove('is-invalid');
            totalAmountInput.classList.add('is-valid');
            if (submitButton) submitButton.disabled = false;
            amountControlDiv.textContent = "Calculated amount matches total amount.";
            amountControlDiv.className = 'form-text text-success';
        } else {
            totalAmountInput.classList.remove('is-valid');
            if (totalAmountInput.value.trim() !== "") {
                totalAmountInput.classList.add('is-invalid');
                amountControlDiv.textContent = "Calculated amount does not match entered Total Amount.";
                amountControlDiv.className = 'form-text text-danger';
            } else {
                totalAmountInput.classList.remove('is-invalid');
                amountControlDiv.textContent = "Please enter the total amount from the receipt.";
                amountControlDiv.className = 'form-text text-warning';
            }
            if (submitButton) submitButton.disabled = true;
        }
    }

    function attachSelectAllHandlers(itemElement) {
        const selectAllCheckbox = itemElement.querySelector(".selectAllUsers");
        const userCheckboxes = itemElement.querySelectorAll(".user-checkbox");
        if (selectAllCheckbox && userCheckboxes.length > 0) {
            selectAllCheckbox.addEventListener("click", function() {
                userCheckboxes.forEach(cb => cb.checked = selectAllCheckbox.checked);
                if (userCheckboxes.length > 0) userCheckboxes[0].dispatchEvent(new Event('change', {
                    bubbles: true
                }));
            });
            userCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('click', function() {
                    if (!this.checked) {
                        if (selectAllCheckbox) selectAllCheckbox.checked = false;
                    } else {
                        let allChecked = true;
                        userCheckboxes.forEach(cb => {
                            if (!cb.checked) allChecked = false;
                        });
                        if (selectAllCheckbox) selectAllCheckbox.checked = allChecked;
                    }
                });
            });
        } else if (selectAllCheckbox) selectAllCheckbox.disabled = true;
    }

    function incrementAmount(buttonElement) {
        const amountInput = buttonElement.closest('.input-group').querySelector('input[name^="amount["]');
        if (amountInput) {
            amountInput.value = (parseInt(amountInput.value) || 0) + 1;
            calculateTotalAmount();
        }
    }

    function decrementAmount(buttonElement) {
        const amountInput = buttonElement.closest('.input-group').querySelector('input[name^="amount["]');
        if (amountInput) {
            amountInput.value = Math.max(1, (parseInt(amountInput.value) || 1) - 1);
            calculateTotalAmount();
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        if (R_EXISTING_ITEMS && R_EXISTING_ITEMS.length > 0) {
            R_EXISTING_ITEMS.forEach(itemData => addItem(itemData));
        } else {
            addItem();
        }

        calculateTotalAmount();
        attachGlobalEventListeners();

        const receiptForm = document.getElementById('receiptForm');
        const confirmModalEl = document.getElementById('confirmReceiptModal');
        const confirmModalInstance = new bootstrap.Modal(confirmModalEl);
        if (receiptForm) {
            receiptForm.addEventListener('submit', function(event) {
                if (readyToSubmitForm) {
                    readyToSubmitForm = false;
                    return;
                }
                event.preventDefault();
                let allAccordionItemsValid = true;
                const accordionItems = receiptForm.querySelectorAll('.accordion-item');
                accordionItems.forEach(item => {
                    const body = item.querySelector('.accordion-body');
                    const collapseDiv = item.querySelector('.accordion-collapse');
                    if (!collapseDiv || !body) {
                        allAccordionItemsValid = false;
                        return;
                    }
                    const collapseId = collapseDiv.id;
                    const currentItemUniqueId = collapseId.substring(collapseId.indexOf('_') + 1);
                    const itemSpecificErrorDiv = body.querySelector(`#bought_for_error_${currentItemUniqueId}`);
                    const userListGroup = body.querySelector(`#bought_for_group_${currentItemUniqueId}`);
                    if (R_ACCOUNT_USERS && R_ACCOUNT_USERS.length > 0) {
                        if (userListGroup) {
                            const checkboxes = userListGroup.querySelectorAll('input.user-checkbox[type="checkbox"]');
                            let atLeastOneChecked = false;
                            if (checkboxes.length > 0) {
                                for (const checkbox of checkboxes)
                                    if (checkbox.checked) {
                                        atLeastOneChecked = true;
                                        break;
                                    }
                                if (!atLeastOneChecked) {
                                    allAccordionItemsValid = false;
                                    if (itemSpecificErrorDiv) itemSpecificErrorDiv.style.display = 'block';
                                } else {
                                    if (itemSpecificErrorDiv) itemSpecificErrorDiv.style.display = 'none';
                                }
                            } else {
                                allAccordionItemsValid = false;
                                if (itemSpecificErrorDiv) {
                                    itemSpecificErrorDiv.textContent = "No user options available to select.";
                                    itemSpecificErrorDiv.style.display = 'block';
                                }
                            }
                        } else allAccordionItemsValid = false;
                    } else if (itemSpecificErrorDiv) itemSpecificErrorDiv.style.display = 'none';
                });
                if (!receiptForm.checkValidity() || !allAccordionItemsValid) {
                    Array.from(receiptForm.elements).forEach(element => {
                        if (element.willValidate && !element.checkValidity()) element.classList.add('is-invalid');
                        else if (element.willValidate && element.checkValidity()) element.classList.remove('is-invalid');
                    });
                    return;
                }
                populateReceiptPreview();
                confirmModalInstance.show();
            });
        }
        document.getElementById('confirmYes').addEventListener('click', function() {
            readyToSubmitForm = true;
            confirmModalInstance.hide();
            const createReceiptButton = document.getElementById('createReceiptBtn');
            if (createReceiptButton) createReceiptButton.click();
            else receiptForm.submit();
        });
    });
</script>

<?php require APPROOT . '/app/views/layouts/footer.php'; ?>