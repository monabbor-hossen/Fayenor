<script src="<?php echo BASE_URL;?>assets/js/jquery-3.6.0.min.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/summernote-lite.min.js"></script>
    
    <script src="<?php echo BASE_URL; ?>assets/js/contract.js"></script>
<?php if (isset($_SESSION['close_tab']) && $_SESSION['close_tab']): ?>
    <?php unset($_SESSION['close_tab']); ?>
    <script>
        setTimeout(function() {
            if (window.opener) {
                window.opener.location.reload();
                window.close();
            } else {
                window.close();
            }
        }, 1500);
    </script>
<?php endif; ?>
</body>
</html>