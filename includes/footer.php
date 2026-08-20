<?php
/**
 * Footer Include File
 * This file contains the footer for all pages
 */
?>

    </main>

    <!-- Footer -->
    <footer>
        <div class="container-fluid">
            <p>&copy; 2025 Logistics Management System. All rights reserved. | Developed for Liverpool Logistics Company</p>
            <p><small>Version 1.0 | <a href="#" style=" text-decoration: none;">Privacy Policy</a> | <a href="#" style=" text-decoration: none;">Terms of Service</a></small></p>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Custom JS -->
    <script src="<?php echo (strpos($_SERVER['PHP_SELF'], '/admin/') !== false || strpos($_SERVER['PHP_SELF'], '/staff/') !== false) ? '/LHZ/js/main.js' : '/LHZ/js/main.js'; ?>"></script>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            const minDate = tomorrow.toISOString().split("T")[0];

            document.querySelectorAll('input[type="date"]:not(.allow-any-date)').forEach(function (input) {
                input.setAttribute("min", minDate);
            });
        });
    </script>


</body>
</html>
