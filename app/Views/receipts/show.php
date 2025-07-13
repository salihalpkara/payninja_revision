<?php require APPROOT . '/app/views/layouts/header.php'; ?>
    <a href="<?php echo URLROOT; ?>/receipts/index/<?php echo $data['receipt']->account_id; ?>" class="btn btn-light"><i class="fa fa-backward"></i> Back</a>
    <br>
    <h1><?php echo $data['receipt']->location; ?></h1>
    <div class="bg-secondary text-white p-2 mb-3">
        Paid by <?php echo $data['receipt']->payer_name; ?> on <?php echo $data['receipt']->receipt_date_time; ?>
    </div>
    <p>Total Amount: <?php echo $data['receipt']->total_amount; ?> <?php echo $data['receipt']->currency; ?></p>

    <hr>

    <div class="card card-body mb-3">
        <h4 class="card-title">Items</h4>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($data['items'] as $item) : ?>
                    <tr>
                        <td><?php echo $item->name; ?></td>
                        <td><?php echo $item->quantity; ?></td>
                        <td><?php echo $item->price; ?></td>
                        <td></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card card-body mb-3">
        <h4 class="card-title">Notes</h4>
        <?php foreach($data['notes'] as $note) : ?>
            <div class="bg-light p-2 mb-3">
                <p><?php echo $note->note; ?></p>
                <small>Written on <?php echo $note->created_at; ?></small>
            </div>
        <?php endforeach; ?>
    </div>

<?php require APPROOT . '/app/views/layouts/footer.php'; ?>
