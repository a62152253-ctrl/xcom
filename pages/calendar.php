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

<link rel="stylesheet" href="/assets/css/calendar.css">

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
            <div class="empty-events">
                <i class="fa-regular fa-calendar"></i>
                <p>Brak zaplanowanych wydarzeń.</p>
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

<script src="/assets/js/calendar.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
