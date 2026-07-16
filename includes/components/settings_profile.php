<!-- ── PROFILE TAB ── -->
                <form method="POST" action="/api/profile.php?action=settings" enctype="multipart/form-data">
            <div class="settings-section">
                <h2 class="settings-section-title">Zdjęcie profilowe</h2>
                <div class="avatar-upload-row">
                    <?php if (!empty($user_data['avatar'])): ?>
                        <img src="<?= htmlspecialchars($user_data['avatar'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ? alt="Image">" class="settings-avatar">
                    <?php else: ?>
                        <div class="settings-avatar settings-avatar-placeholder"><?= htmlspecialchars(strtoupper(substr($user_data['full_name'], 0, 1)), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
                    <?php endif; ?>
                    <div>
                        <label class="btn btn-secondary" style="cursor:pointer;width:auto">
                            <i class="fa-solid fa-camera"></i> Zmień zdjęcie
                            <input type="file" name="avatar" accept="image/*" style="display:none" onchange="previewAvatar(this)">
                        </label>
                        <p style="font-size:.75rem;color:var(--text-muted);margin-top:.5rem">JPG, PNG, max 2MB</p>
                    </div>
                </div>
            </div>

            <div class="settings-section">
                <h2 class="settings-section-title">Dane osobowe</h2>
                <div class="form-group">
                    <label class="form-label">Imię i nazwisko</label>
                    <input class="form-control" type="text" name="full_name" value="<?= htmlspecialchars($user_data['full_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" required maxlength="255">
                </div>
                <div class="form-group">
                    <label class="form-label">E-mail <span style="color:var(--text-muted);font-size:.8rem">(tylko do odczytu)</span></label>
                    <input class="form-control" type="email" value="<?= htmlspecialchars($user_data['email'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" disabled>
                </div>
            </div>

            <div class="settings-section">
                <h2 class="settings-section-title">Język aplikacji</h2>
                <div class="form-group">
                    <select class="form-control" name="language" style="max-width:240px">
                        <option value="pl" <?= ($user_data['language'] ?? 'pl') === 'pl' ? 'selected' : '' ?>>🇵🇱 Polski</option>
                        <option value="en" <?= ($user_data['language'] ?? 'pl') === 'en' ? 'selected' : '' ?>>🇬🇧 English</option>
                    </select>
                </div>
            </div>

            <button class="btn btn-primary" type="submit" style="width:auto"><i class="fa-solid fa-floppy-disk"></i> Zapisz zmiany</button>
        </form>

