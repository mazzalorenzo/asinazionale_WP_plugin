<?php
if (!empty($_GET['asi_error'])) {
    $error_msg = sanitize_text_field(wp_unslash($_GET['asi_error']));
    do_action('asinazionale_show_error', $error_msg, 'Errore ASI Nazionale');
}
?>
<div class="container my-3">
    <form method="post" class="asinazionale">
        <input type="hidden" name="asi_current_url" value="<?php echo esc_url(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : ''); ?>">
        <div class="mb-3">
            <label for="cf" class="form-label">Codice Fiscale</label>
            <input type="text" name="cf" id="cf" class="form-control" placeholder="Inserisci il Codice Fiscale" maxlength="16" required>
            <small id="cfHelp" class="form-text text-muted">Inserisci il tuo codice fiscale per scaricare la tessera ASI.</small>
        </div>
        <div class="mb-3">
            <button type="submit" name="asinazionale" class="btn btn-primary">Scarica Tessera</button>
        </div>
    </form>
</div>


