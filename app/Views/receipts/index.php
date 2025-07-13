<?php require APPROOT . '/app/views/layouts/header.php'; ?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center my-lg-4 my-sm-2">
        <h1 class="text-center mb-0"><?php echo htmlspecialchars($data['account']->account_name); ?> Receipts</h1>
        <a href="<?php echo URLROOT; ?>/receipts/add/<?php echo htmlspecialchars($data['account']->id); ?>" class="btn btn-success">
            <i class="bi bi-plus-lg me-2"></i>Add New Receipt
        </a>
    </div>

    <div class="row">
        <div class="col-12">
            <table id="ReceiptTable" class="table table-striped table-dark table-hover table-bordered" style="width:100%;">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Payer</th>
                        <th>Location</th>
                        <th>Amount Paid</th>
                        <th>Item Count</th>
                        <th data-dt-order="disable">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['receipts'] as $receipt) : ?>
                        <?php
                        // 1. Tüm aranabilir metinleri birleştireceğimiz bir değişken oluşturuyoruz.
                        $searchable_text = '';
                        $searchable_text .= $receipt->location . ' ';
                        $searchable_text .= $receipt->payer_fname . ' ' . $receipt->payer_lname . ' ';
                        $searchable_text .= $receipt->currency . ' ';

                        // 2. Ürün bilgilerini bu değişkene ekliyoruz.
                        if (is_array($receipt->items)) {
                            foreach ($receipt->items as $item) {
                                $searchable_text .= $item->name . ' ';
                                $searchable_text .= $item->bought_for_names . ' ';
                            }
                        }
                        // 3. Notları bu değişkene ekliyoruz.
                        if (is_array($receipt->notes)) {
                            foreach ($receipt->notes as $note) {
                                $searchable_text .= $note . ' ';
                            }
                        }
                        ?>
                        <tr class="receipt-main-row" data-receipt-id="<?php echo $receipt->id; ?>">
                            <td><?php echo htmlspecialchars(date("Y-m-d H:i", strtotime($receipt->receipt_date_time))); ?></td>
                            <td><?php echo htmlspecialchars($receipt->payer_fname . ' ' . $receipt->payer_lname); ?></td>
                            <td><?php echo htmlspecialchars($receipt->location); ?></td>
                            <td data-order="<?php echo (float)$receipt->total_amount; ?>"><?php echo htmlspecialchars(number_format((float)$receipt->total_amount, 2, '.', '') . " " . $receipt->currency); ?></td>
                            <td><?php echo is_array($receipt->items) ? count($receipt->items) : 0; ?></td>
                            <td>
                                <a href="<?php echo URLROOT; ?>/receipts/edit/<?php echo $receipt->id; ?>" class="btn btn-sm btn-primary me-1" title="Edit Receipt">
                                    Edit<i class="bi bi-pencil-square ms-2"></i>
                                </a>
                                <button class="btn btn-sm btn-danger action-delete-receipt" data-id="<?php echo $receipt->id; ?>" title="Delete Receipt">
                                    Delete<i class="bi bi-trash3 ms-2"></i>
                                </button>
                            </td>
                            <td class="d-none"><?php echo htmlspecialchars($searchable_text); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteConfirmModalLabel">Confirm Receipt Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this receipt? This action cannot be undone.</p>
                <div id="deleteReceiptPreview" class="receipt-preview">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Yes, Delete</button>
            </div>
        </div>
    </div>
</div>


<script>
    const R_ACCOUNT_ID = <?php echo htmlspecialchars($data['account']->id); ?>; // JavaScript'e hesap ID'sini aktar
    const allReceiptsData = <?php echo json_encode($data['receipts']); ?>;
    const receiptsDataMap = {};
    allReceiptsData.forEach(receipt => {
        receiptsDataMap[receipt.id] = receipt;
    });

    // Para birimi ve tarih formatlama fonksiyonları (new_receipt.php'den kopyalandı)
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

    // Silme onayı modalı için fiş önizlemesini dolduran genel fonksiyon
    function populateGenericReceiptPreview(receiptData, previewElementId) {
        const previewElement = document.getElementById(previewElementId);
        if (!previewElement || !receiptData) return;

        const effectiveCurrency = receiptData.currency || 'USD'; // Fallback currency
        let html = '';
        const unknown = 'N/A';

        html += `<h5 class="preview-section-title"><i class="bi bi-geo-alt-fill"></i> Location:</h5><p class="preview-data">${receiptData.location || unknown}</p>`;
        html += `<h5 class="preview-section-title"><i class="bi bi-calendar-event-fill"></i> Date & Time:</h5><p class="preview-data">${formatReceiptDate(receiptData.receipt_date_time)}</p>`;

        const payerName = (receiptData.payer_fname || receiptData.payer_lname) ? `${receiptData.payer_fname || ''} ${receiptData.payer_lname || ''}`.trim() : unknown;
        html += `<h5 class="preview-section-title"><i class="bi bi-person-fill"></i> Payer:</h5><p class="preview-data">${payerName}</p>`;

        html += `<hr class="dashed">`;
        html += `<div class="section-header">ITEMS</div>`;
        html += `<div class="item-section">`;

        if (!receiptData.items || receiptData.items.length === 0) {
            html += `<p class="text-center text-muted">No items for this receipt.</p>`;
        } else {
            receiptData.items.forEach(item => {
                const itemName = item.name ? item.name.trim() || 'Unnamed Item' : 'Unknown Item';
                const quantity = parseInt(item.quantity) || 0;
                const price = parseFloat(item.price) || 0;
                const itemTotal = quantity * price;

                html += `<div class="item-line">`;
                html += `  <span class="item-name-qty">${quantity} x ${itemName}</span>`;
                html += `  <span class="item-price-total">${formatCurrency(itemTotal, effectiveCurrency)}</span>`;
                html += `</div>`;
                if (item.bought_for_names && item.bought_for_names !== 'N/A') {
                    html += `<div class="bought-for-list">&hookrightarrow; For: ${item.bought_for_names}</div>`;
                }
            });
        }
        html += `</div>`;
        html += `<hr class="dashed">`;

        const totalAmount = parseFloat(receiptData.total_amount) || 0;
        html += `<div class="totals-line">`;
        html += `  <span>TOTAL:</span>`;
        html += `  <span>${formatCurrency(totalAmount, effectiveCurrency)}</span>`;
        html += `</div>`;

        if (receiptData.notes && receiptData.notes.length > 0) {
            html += `<hr class="dashed">`;
            html += `<h5 class="preview-section-title"><i class="bi bi-sticky-fill"></i> Note:</h5>`;
            receiptData.notes.forEach(note => {
                html += `<p class="preview-data" style="white-space: pre-wrap;">${note}</p>`;
            });
        }
        html += `<p class="footer-note">This information will be permanently deleted.</p>`;
        previewElement.innerHTML = html;
    }


    function formatReceiptDetails(receiptData) { // Bu fonksiyon DataTable child row için
        let itemsHtml = '<p class="m-0">No items available.</p>';
        if (receiptData.items && receiptData.items.length > 0) {
            itemsHtml = '<table class="table table-sm table-borderless table-striped-columns table-dark m-0" style="background-color: #343a40 !important;">';
            itemsHtml += '<thead class="table-light"><tr><th scope="col">Item Name</th><th scope="col">Price</th><th scope="col">Quantity</th><th scope="col">Bought For</th></tr></thead><tbody>';
            receiptData.items.forEach(item => {
                itemsHtml += `<tr>
                                <td>${item.name}</td>
                                <td>${item.price} ${receiptData.currency}</td>
                                <td>${item.quantity}</td>
                                <td>${item.bought_for_names}</td>
                             </tr>`;
            });
            itemsHtml += '</tbody></table></div>';
        }
        let notesHtml = '<p class="m-0">No notes available.</p>';
        if (receiptData.notes && receiptData.notes.length > 0) {
            notesHtml = '<h6 class="mt-2 mb-1">Notes:</h6><div>';
            receiptData.notes.forEach(note => notesHtml += `${note.replace(/\n/g, '<br>')}<br/>`);
            notesHtml += '</div>';
        }
        return `<div class="p-3 bg-secondary text-white">${itemsHtml}<div class="mt-2">${notesHtml}</div></div>`;
    }

    document.addEventListener('DOMContentLoaded', function() {
        let table = new DataTable('#ReceiptTable', {
            responsive: true,
            search: {
                smart: false
            },
            order: [
                [0, 'desc']
            ],
            columnDefs: [{
                    targets: '_all',
                    defaultContent: ''
                },
                {
                    responsivePriority: 1,
                    targets: 0
                },
                {
                    responsivePriority: 2,
                    targets: 3
                },
                {
                    responsivePriority: 3,
                    targets: 1
                },
                {
                    targets: 5,
                    orderable: false,
                    searchable: false
                }, // Actions kolonu sıralanamaz ve aranamaz
                {
                    targets: 6,
                    visible: false,
                    searchable: true
                } // YENİ: Gizli arama kolonu (index 6)
            ]
        });

        let receiptIdToDelete = null;
        const deleteConfirmModalEl = document.getElementById('deleteConfirmModal');
        const deleteConfirmModalInstance = new bootstrap.Modal(deleteConfirmModalEl);

        document.querySelector('#ReceiptTable tbody').addEventListener('click', function(event) {
            const deleteButton = event.target.closest('.action-delete-receipt');
            const mainRow = event.target.closest('tr.receipt-main-row');

            if (deleteButton) {
                receiptIdToDelete = deleteButton.dataset.id;
                const receiptData = receiptsDataMap[receiptIdToDelete];
                populateGenericReceiptPreview(receiptData, 'deleteReceiptPreview');
                deleteConfirmModalInstance.show();
            } else if (mainRow) {
                const dtRow = table.row(mainRow);
                const receiptId = mainRow.dataset.receiptId;
                const receiptData = receiptsDataMap[receiptId];

                if (dtRow.child.isShown()) {
                    dtRow.child.hide();
                } else if (receiptData) {
                    // Yardımcı fonksiyon ile detayları göster ve vurgula
                    showChildRowAndHighlight(dtRow, receiptData);
                }
            }
        });

        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (receiptIdToDelete) {
                fetch('<?php echo URLROOT; ?>/receipts/delete', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            receipt_id: receiptIdToDelete,
                            account_id: R_ACCOUNT_ID
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        deleteConfirmModalInstance.hide();
                        if (data.success) {
                            // DataTables'dan satırı kaldır
                            table.row(document.querySelector(`tr[data-receipt-id="${receiptIdToDelete}"]`)).remove().draw(false);
                            // Başarı mesajı (örn: toast notification)
                            alert('Receipt deleted successfully!'); // Basit alert, daha iyi bir bildirim sistemi kullanabilirsiniz
                        } else {
                            alert('Error deleting receipt: ' + (data.message || 'Unknown error'));
                        }
                        receiptIdToDelete = null; // ID'yi temizle
                    })
                    .catch(error => {
                        deleteConfirmModalInstance.hide();
                        alert('An error occurred: ' + error);
                        receiptIdToDelete = null;
                    });
            }
        });

        table.on('search.dt', function() {
            const searchTerm = table.search();

            table.rows().every(function() {
                if (this.child.isShown()) this.child.hide();
            });

            if (searchTerm) {
                table.rows({
                    search: 'applied'
                }).every(function() {
                    const receiptId = this.node().dataset.receiptId;
                    const receiptData = receiptsDataMap[receiptId];
                    if (receiptData) {
                        let childDataString = '';
                        if (receiptData.items && receiptData.items.length > 0) {
                            receiptData.items.forEach(item => {
                                childDataString += (item.name || '') + ' ' + (item.bought_for_names || '') + ' ';
                            });
                        }
                        if (receiptData.notes && receiptData.notes.length > 0) {
                            receiptData.notes.forEach(note => {
                                childDataString += note + ' ';
                            });
                        }

                        if (childDataString.toLowerCase().includes(searchTerm.toLowerCase())) {
                            // Yardımcı fonksiyon ile detayları göster ve vurgula
                            showChildRowAndHighlight(this, receiptData);
                        }
                    }
                });
            }
        });

        function highlightSearchTerm(targetNode, searchTerm) {
            // targetNode: Vurgulama yapılacak olan DOM elementi (örn: tbody veya açılan detay satırı)
            // searchTerm: Vurgulanacak metin
            if (!targetNode) return;

            const marker = new Mark(targetNode);

            // Önceki vurgulamaları temizle, sonra yenisini uygula
            marker.unmark({
                done: function() {
                    if (searchTerm) {
                        marker.mark(searchTerm, {
                            separateWordSearch: false, // "market alışverişi" gibi tam ifadeleri de bulması için
                            diacritics: true, // Aksanları ve farklı harfleri de eşleştirmeye çalışır
                            accuracy: "partially" // Kelimenin bir kısmı eşleşse bile bulur
                        });
                    }
                }
            });
        }

        function showChildRowAndHighlight(dtRow, receiptData) {
            if (!dtRow.child.isShown()) {
                dtRow.child(formatReceiptDetails(receiptData)).show();

                // Detay satırı gösterildikten sonra içini vurgula
                const childRowElement = dtRow.child()[0]; // gösterilen child row'un DOM elementini al
                const searchTerm = table.search();
                highlightSearchTerm(childRowElement, searchTerm);
            }
        }

        table.on('draw.dt', function() {
            const searchTerm = table.search();
            highlightSearchTerm(document.querySelector('#ReceiptTable tbody'), searchTerm);
        });
    });
</script>

<?php require APPROOT . '/app/views/layouts/footer.php'; ?>