<?php
require_once __DIR__ . '/../includes/header.php';

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

$month = (int)($_GET['month'] ?? date('m'));
$year = (int)($_GET['year'] ?? date('Y'));

if ($month < 1 || $month > 12) $month = date('m');
if ($year < 2000 || $year > 2100) $year = date('Y');

$start = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
$end = date('Y-m-t', strtotime($start));

$stmt = $db->prepare("SELECT * FROM calendar_events WHERE user_id = ? AND event_date BETWEEN ? AND ? ORDER BY event_date ASC");
$stmt->execute([$user_id, $start, $end]);
$events = $stmt->fetchAll();

$prev_month = $month - 1;
$prev_year = $year;
if ($prev_month < 1) {
    $prev_month = 12;
    $prev_year--;
}

$next_month = $month + 1;
$next_year = $year;
if ($next_month > 12) {
    $next_month = 1;
    $next_year++;
}

$months = ['', 'Styczeń', 'Luty', 'Marzec', 'Kwiecień', 'Maj', 'Czerwiec', 'Lipiec', 'Sierpień', 'Wrzesień', 'Październik', 'Listopad', 'Grudzień'];
$weekdays = ['Poniedziałek', 'Wtorek', 'Środa', 'Czwartek', 'Piątek', 'Sobota', 'Niedziela'];

$first_day = new \DateTime("$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01");
$last_day = new \DateTime($end);
$days_in_month = $last_day->format('d');
$first_weekday = (int)$first_day->format('N') - 1;

$events_by_date = [];
foreach ($events as $e) {
    $events_by_date[$e['event_date']][] = $e;
}
?>

<style>
.calendar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.calendar-header h1 {
    margin: 0;
    font-size: 1.8rem;
    font-weight: 700;
}

.calendar-nav {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.calendar-nav-btn {
    background: var(--primary);
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.2s ease;
}

.calendar-nav-btn:hover {
    background: var(--primary-dark);
    transform: translateY(-1px);
}

.calendar-month-year {
    font-size: 1.1rem;
    font-weight: 700;
    min-width: 180px;
    text-align: center;
}

.calendar-main {
    display: grid;
    grid-template-columns: 1fr;
    gap: 2rem;
}

.calendar-card {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 2rem;
    box-shadow: var(--shadow-sm);
    grid-column: 1;
}

.calendar-table {
    width: 100%;
    border-collapse: collapse;
}

.calendar-weekday-header {
    background: var(--bg-tertiary);
    padding: 1.25rem;
    font-weight: 700;
    text-align: center;
    font-size: 1rem;
    color: var(--text-secondary);
    border-bottom: 2px solid var(--border-color);
}

.calendar-day-cell {
    min-height: 120px;
    border: 1px solid var(--border-color);
    padding: 1rem;
    vertical-align: top;
    position: relative;
    cursor: pointer;
    transition: all 0.2s ease;
    background: var(--bg-primary);
}

.calendar-day-cell:hover {
    background: var(--bg-secondary);
    border-color: var(--primary);
}

.calendar-day-cell.other-month {
    background: var(--bg-secondary);
    color: var(--text-muted);
    pointer-events: none;
}

.calendar-day-cell.today {
    background: rgba(59, 130, 246, 0.1);
    border-color: var(--primary);
}

.calendar-day-cell.today .day-number {
    color: var(--primary);
    font-weight: 700;
}

.day-number {
    font-weight: 700;
    font-size: 1.25rem;
    margin-bottom: 0.75rem;
    color: var(--text-primary);
}

.day-events {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.day-event-dot {
    width: 3px;
    height: 3px;
    border-radius: 50%;
    background: var(--primary);
    display: inline-block;
    margin-right: 0.3rem;
}

.events-sidebar {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: var(--shadow-sm);
    grid-column: 1;
}

.events-sidebar h2 {
    margin: 0 0 1.5rem 0;
    font-size: 1.1rem;
    font-weight: 700;
}

.event-item {
    padding: 1rem;
    margin-bottom: 0.75rem;
    background: var(--bg-secondary);
    border-left: 4px solid var(--primary);
    border-radius: 6px;
    transition: all 0.2s ease;
    cursor: pointer;
}

.event-item:hover {
    transform: translateX(4px);
    background: var(--bg-tertiary);
}

.event-date {
    font-size: 0.8rem;
    color: var(--text-muted);
    margin-bottom: 0.25rem;
}

.event-title {
    font-weight: 600;
    font-size: 0.95rem;
    color: var(--text-primary);
    margin-bottom: 0.25rem;
}

.event-time {
    font-size: 0.8rem;
    color: var(--primary);
}

.empty-events {
    text-align: center;
    color: var(--text-muted);
    padding: 2rem 1rem;
}

.empty-events i {
    font-size: 2.5rem;
    opacity: 0.3;
    margin-bottom: 0.75rem;
}

@media (max-width: 1024px) {
    .calendar-main {
        grid-template-columns: 1fr;
    }
    .calendar-day-cell {
        min-height: 100px;
    }
}
</style>

<!-- Page Header -->
<div class="calendar-header animate-fade">
    <div>
        <h1><i class="fa-solid fa-calendar-days"></i> Kalendarz</h1>
        <p style="margin: 0.5rem 0 0 0; color: var(--text-muted);">Zarządzaj swoimi wydarzeniami i terminami.</p>
    </div>
    <button class="btn btn-primary" onclick="openAddEventModal()">
        <i class="fa-solid fa-plus"></i> Nowe wydarzenie
    </button>
</div>

<!-- Calendar Navigation -->
<div style="display: flex; justify-content: center; margin-bottom: 2rem; gap: 1rem;">
    <button class="calendar-nav-btn" onclick="goToMonth(<?= $prev_month ?>, <?= $prev_year ?>)">
        <i class="fa-solid fa-chevron-left"></i> Poprzedni
    </button>
    <div class="calendar-month-year">
        <?= $months[$month] ?> <?= $year ?>
    </div>
    <button class="calendar-nav-btn" onclick="goToMonth(<?= $next_month ?>, <?= $next_year ?>)">
        Następny <i class="fa-solid fa-chevron-right"></i>
    </button>
    <button class="calendar-nav-btn" onclick="goToToday()" style="background: var(--success);">
        <i class="fa-solid fa-calendar-today"></i> Dziś
    </button>
</div>

<!-- Main Calendar Grid -->
<div class="calendar-main animate-slide-up">
    <!-- Calendar -->
    <div class="calendar-card">
        <table class="calendar-table">
            <thead>
                <tr>
                    <?php foreach ($weekdays as $wd): ?>
                    <th class="calendar-weekday-header"><?= substr($wd, 0, 3) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php
                $day = 1;
                for ($week = 0; $week < 6; $week++) {
                    echo '<tr>';
                    for ($weekday = 0; $weekday < 7; $weekday++) {
                        if (($week == 0 && $weekday < $first_weekday) || $day > $days_in_month) {
                            echo '<td class="calendar-day-cell other-month"></td>';
                        } else {
                            $date = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-" . str_pad($day, 2, '0', STR_PAD_LEFT);
                            $is_today = $date === date('Y-m-d');
                            $has_events = isset($events_by_date[$date]);
                            echo '<td class="calendar-day-cell' . ($is_today ? ' today' : '') . '" onclick="showDayEvents(\'' . $date . '\')">';
                            echo '<div class="day-number">' . $day . '</div>';
                            if ($has_events) {
                                echo '<div class="day-events">';
                                foreach (array_slice($events_by_date[$date], 0, 2) as $e) {
                                    echo '<div style="font-size: 0.7rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">';
                                    echo '<span class="day-event-dot"></span>' . htmlspecialchars($e['title'], ENT_QUOTES, 'UTF-8');
                                    echo '</div>';
                                }
                                if (count($events_by_date[$date]) > 2) {
                                    echo '<div style="font-size: 0.7rem; color: var(--primary); font-weight: 600;">+' . (count($events_by_date[$date]) - 2) . ' więcej</div>';
                                }
                                echo '</div>';
                            }
                            echo '</td>';
                            $day++;
                        }
                    }
                    echo '</tr>';
                    if ($day > $days_in_month) break;
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- Upcoming Events Sidebar -->
    <div class="events-sidebar">
        <h2><i class="fa-solid fa-list"></i> Nadchodzące wydarzenia</h2>
        <?php
        $upcoming = [];
        foreach ($events as $e) {
            if (strtotime($e['event_date']) >= strtotime('today')) {
                $upcoming[] = $e;
            }
        }
        usort($upcoming, function($a, $b) { return strtotime($a['event_date']) - strtotime($b['event_date']); });

        if (empty($upcoming)): ?>
            <div class="empty-state-premium" style="padding:1.5rem">
                <div class="es-icon" style="font-size:2rem">📅</div>
                <div class="es-title" style="font-size:1.1rem">Brak wydarzeń</div>
                <div class="es-sub" style="font-size:0.9rem">Masz czysty grafik.</div>
                <button class="es-btn" style="padding:0.5rem 1rem;font-size:0.9rem" onclick="openAddEventModal()"><i class="fa-solid fa-plus"></i> Dodaj</button>
            </div>
        <?php else: ?>
            <?php foreach (array_slice($upcoming, 0, 10) as $e): ?>
            <div class="event-item" onclick="editEvent(<?= (int)$e['id'] ?>)">
                <div class="event-date"><?= date('d M Y', strtotime($e['event_date'])) ?></div>
                <div class="event-title"><?= sanitize($e['title']) ?></div>
                <?php if ($e['event_time']): ?>
                <div class="event-time"><i class="fa-regular fa-clock"></i> <?= substr($e['event_time'], 0, 5) ?></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/modals/event_modal.php'; ?>

<script>
let editingEventId = null;

function goToMonth(m, y) {
    if (!m || !y || isNaN(m) || isNaN(y)) return;
    window.location.href = `/pages/calendar.php?month=${parseInt(m)}&year=${parseInt(y)}`;
}

function goToToday() {
    const today = new Date();
    window.location.href = `/pages/calendar.php?month=${today.getMonth() + 1}&year=${today.getFullYear()}`;
}

function openAddEventModal(date = null) {
    editingEventId = null;
    document.getElementById('event-id').value = '';
    document.getElementById('event-title').value = '';
    document.getElementById('event-date').value = date || new Date().toISOString().split('T')[0];
    document.getElementById('event-time').value = '';
    document.getElementById('event-description').value = '';
    document.getElementById('event-modal-title').textContent = 'Nowe wydarzenie';
    document.getElementById('event-delete-btn').style.display = 'none';
    document.getElementById('event-modal').classList.add('active');
}

function showDayEvents(date) {
    openAddEventModal(date);
}

async function editEvent(id) {
    const json = await apiGet(`/api/calendar_detail.php?id=${parseInt(id)}`);
    if (!json?.event) {
        Toast.error('Nie udało się załadować wydarzenia.');
        return;
    }
    const e = json.event;

    editingEventId = id;
    document.getElementById('event-id').value = id;
    document.getElementById('event-title').value = e.title;
    document.getElementById('event-date').value = e.event_date;
    document.getElementById('event-time').value = e.event_time || '';
    document.getElementById('event-description').value = e.description || '';
    document.getElementById('event-modal-title').textContent = 'Edytuj wydarzenie';
    document.getElementById('event-delete-btn').style.display = 'block';
    document.getElementById('event-modal').classList.add('active');
}

function closeEventModal() {
    document.getElementById('event-modal').classList.remove('active');
    editingEventId = null;
}

async function saveEvent() {
    const title = document.getElementById('event-title').value.trim();
    const date = document.getElementById('event-date').value;

    if (!title || !date) {
        Toast.error('Podaj tytuł i datę.');
        return;
    }

    const btn = document.querySelector('#event-modal .btn-primary');
    btn.disabled = true;

    const payload = {
        id: editingEventId,
        title,
        event_date: date,
        event_time: document.getElementById('event-time').value || null,
        description: document.getElementById('event-description').value
    };

    const action = editingEventId ? 'update' : 'create';
    const json = await apiPost(`/api/calendar.php?action=${action}`, payload);

    btn.disabled = false;
    if (json.success) {
        Toast.success(editingEventId ? 'Wydarzenie zaktualizowane!' : 'Wydarzenie utworzone!');
        closeEventModal();
        setTimeout(() => location.reload(), 800);
    } else {
        Toast.error(json.error || 'Błąd zapisu');
    }
}

async function deleteEvent() {
    if (!editingEventId) return;
    confirmDialog('Usunąć to wydarzenie?', async () => {
        const json = await apiPost('/api/calendar.php?action=delete', { id: parseInt(editingEventId) });
        if (json.success) {
            Toast.success('Wydarzenie usunięte.');
            closeEventModal();
            setTimeout(() => location.reload(), 800);
        } else {
            Toast.error(json.error || 'Błąd usuwania');
        }
    }, true);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
