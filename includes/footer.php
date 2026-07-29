<?php
// includes/footer.php
?>
            </main>
        </div>
    </div>

    <!-- Toast container -->
    <div id="toast-container" class="toast-container"></div>

    <!-- Global App JS (loads after page content) -->
    <script src="/assets/js/app.js"></script>

<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js').catch(err => {
                console.error('ServiceWorker registration failed: ', err);
            });
        });
    }
</script>
</body>
</html>
