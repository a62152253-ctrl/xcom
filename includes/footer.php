<?php
// includes/footer.php
?>
            </main>
        </div>
    </div>

    <!-- Toast container -->
    <div id="toast-container" class="toast-container"></div>

    <!-- Global Search Modal -->
    <div class="modal-overlay" id="globalSearchModal">
        <div class="modal-window" style="max-width: 500px;">
            <div class="modal-body" style="padding: 1rem;">
                <div class="search-bar" style="max-width: 100%;">
                    <i class="fa-solid fa-search"></i>
                    <input type="text" id="globalSearchInput" placeholder="Szukaj..." autocomplete="off">
                </div>
                <div id="globalSearchResults" class="search-results-list" style="margin-top: 1rem; max-height: 300px; overflow-y: auto;">
                </div>
            </div>
        </div>
    </div>

    <!-- Global App JS (loads after page content) -->
    <script src="/assets/js/app.js"></script>

</body>
</html>
