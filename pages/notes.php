<?php
// pages/notes.php - Notion Workspace
require_once __DIR__ . '/../includes/header.php';

$active_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
?>
<link rel="stylesheet" href="/assets/css/notion.css">

<div class="notion-workspace" id="notion-workspace-container" data-active-id="<?= $active_id ?>">
    <!-- Dynamic Workspace Header -->
    <div class="notion-header-bar" id="notion-header-bar" style="display:none">
        <div class="notion-breadcrumbs" id="notion-breadcrumbs"></div>
        <div class="notion-header-actions">
            <span class="save-status" id="notion-save-status"><i class="fa-solid fa-circle-check"></i> Zapisano</span>
            <button class="notion-action-btn" id="btn-favorite" onclick="togglePageFavorite()" title="Ulubione"><i class="fa-regular fa-star"></i></button>
            <button class="notion-action-btn" id="btn-archive" onclick="togglePageArchive()" title="Zarchiwizuj"><i class="fa-solid fa-box-archive"></i></button>
            <button class="notion-action-btn btn-danger-icon" id="btn-trash" onclick="togglePageTrash()" title="Przenieś do kosza"><i class="fa-solid fa-trash-can"></i></button>
        </div>
    </div>

    <!-- Page Content Container -->
    <div class="notion-canvas" id="notion-canvas" style="display:none">
        <!-- Icon & Title Section -->
        <div class="notion-page-meta">
            <div class="notion-page-icon-wrapper">
                <button class="notion-icon-picker-trigger" id="page-icon-btn" onclick="toggleEmojiPicker(event)">📝</button>
                <div class="notion-emoji-picker" id="notion-emoji-picker" style="display:none">
                    <span onclick="selectEmoji('📝')">📝</span>
                    <span onclick="selectEmoji('💡')">💡</span>
                    <span onclick="selectEmoji('📅')">📅</span>
                    <span onclick="selectEmoji('🚀')">🚀</span>
                    <span onclick="selectEmoji('⭐')">⭐</span>
                    <span onclick="selectEmoji('📌')">📌</span>
                    <span onclick="selectEmoji('🎯')">🎯</span>
                    <span onclick="selectEmoji('💼')">💼</span>
                    <span onclick="selectEmoji('📂')">📂</span>
                    <span onclick="selectEmoji('🔒')">🔒</span>
                </div>
            </div>
            <div class="notion-title-wrapper">
                <input type="text" class="notion-page-title" id="notion-page-title" placeholder="Bez tytułu" oninput="handleTitleInput()">
            </div>
        </div>

        <!-- Block Editor Area -->
        <div class="notion-editor" id="notion-editor"></div>

        <!-- Subpages Index Widget -->
        <div class="notion-subpages-widget">
            <div class="subpages-widget-header">
                <span>Podstrony</span>
                <button onclick="createNewSubpage()" class="btn btn-secondary btn-sm"><i class="fa-solid fa-plus"></i> Dodaj podstronę</button>
            </div>
            <div id="subpages-list" class="subpages-list"></div>
        </div>
    </div>

    <!-- Empty State / Landing -->
    <div class="notion-empty-state" id="notion-empty-state">
        <div class="nes-emoji">🌐</div>
        <h2 class="nes-title">Witaj w swoim Obszarze Roboczym</h2>
        <p class="nes-text">Wybierz stronę z panelu bocznego, aby rozpocząć edycję lub stwórz nową stronę główną, by zorganizować swoje notatki, wiedzę i zadania w stylu Notion.</p>
        <button class="btn btn-primary" onclick="createNewPageInSidebar(null)"><i class="fa-solid fa-plus"></i> Stwórz pierwszą stronę</button>
    </div>
</div>

<!-- Slash Command Menu Dropdown -->
<div class="notion-slash-menu" id="notion-slash-menu" style="display:none">
    <div class="nsm-item" onclick="convertActiveBlock('p')"><i class="fa-solid fa-paragraph"></i> <span>Tekst (paragraf)</span><kbd>/p</kbd></div>
    <div class="nsm-item" onclick="convertActiveBlock('h1')"><i class="fa-solid fa-heading"></i> <span>Nagłówek 1</span><kbd>/h1</kbd></div>
    <div class="nsm-item" onclick="convertActiveBlock('h2')"><i class="fa-solid fa-heading"></i> <span>Nagłówek 2</span><kbd>/h2</kbd></div>
    <div class="nsm-item" onclick="convertActiveBlock('todo')"><i class="fa-solid fa-square-check"></i> <span>Checklista (zadanie)</span><kbd>/todo</kbd></div>
    <div class="nsm-item" onclick="convertActiveBlock('bullet')"><i class="fa-solid fa-list-ul"></i> <span>Lista punktowana</span><kbd>/list</kbd></div>
    <div class="nsm-item" onclick="convertActiveBlock('quote')"><i class="fa-solid fa-quote-left"></i> <span>Cytat</span><kbd>/quote</kbd></div>
    <div class="nsm-item" onclick="convertActiveBlock('code')"><i class="fa-solid fa-code"></i> <span>Blok kodu</span><kbd>/code</kbd></div>
    <div class="nsm-item" onclick="convertActiveBlock('table')"><i class="fa-solid fa-table"></i> <span>Tabela</span><kbd>/table</kbd></div>
</div>

<script src="/assets/js/notion-sidebar.js"></script>
<script src="/assets/js/notion-editor.js"></script>
<script src="/assets/js/notion-commands.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>