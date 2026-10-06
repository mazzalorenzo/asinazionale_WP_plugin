<?php if (!empty($_GET['asi_error'])): ?>
    <div class="alert alert-danger" role="alert">
        <?php echo esc_html(wp_unslash($_GET['asi_error'])); ?>
    </div>
<?php endif; ?>
<div class="container">
    <form method="post" class="asinazionale">
        <div class="mb-3">
            <label for="cf" class="form-label"></label>
            <input type="text" name="cf" id="cf" class="form-control" placeholder="Codice Fiscale" required>
            <small id="cfHelp" class="form-text text-muted">Inserisci il tuo codice fiscale</small>

        </div>
        <div class="">
            <button type="submit" name="asinazionale" class="btn btn-primary">Scarica Tessera</button>
        </div>
    </form>
</div>