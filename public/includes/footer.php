    </div>

    <script src="assets/js/api.js?v=5"></script>
    <script src="assets/js/config.js?v=5"></script>
    <script src="assets/js/toast.js?v=5"></script>

<?php
if (!empty($pageScripts)) {
    foreach ($pageScripts as $script) {
        echo '    <script src="' . $script . '"></script>' . PHP_EOL;
    }
}
?>

</body>
</html>
