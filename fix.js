const fs = require('fs');
let c1 = fs.readFileSync('includes/components/dashboard_kpi.php', 'utf8');
c1 = c1.replace(/onclick="window\.location\.href='\/pages\/projects\.php'"/g, '');
c1 = c1.replace(/onclick="window\.location\.href='\/pages\/tasks\.php'"/g, '');
c1 = c1.replace(/style="--pkpi-color:#6366f1;--pkpi-light:rgba\(99,102,241,\.1\);--pkpi-grad:linear-gradient\(90deg,#6366f1,#8b5cf6\);--pkpi-glow:rgba\(99,102,241,\.1\)"/g, '');
c1 = c1.replace(/style="--pkpi-color:#06b6d4;--pkpi-light:rgba\(6,182,212,\.1\);--pkpi-grad:linear-gradient\(90deg,#06b6d4,#0ea5e9\);--pkpi-glow:rgba\(6,182,212,\.1\)"/g, '');
fs.writeFileSync('includes/components/dashboard_kpi.php', c1);

let c2 = fs.readFileSync('includes/components/dashboard_projects_activity.php', 'utf8');
c2 = c2.replace(/onclick="window\.location\.href='\/pages\/tasks\.php\?project_id=<\?php echo htmlspecialchars\(\$proj\['id'\], ENT_QUOTES, 'UTF-8'\); \?>'"/g, '');
c2 = c2.replace(/style="--proj-color:<\?php echo htmlspecialchars\(\$proj\['color'\], ENT_QUOTES, 'UTF-8'\); \?>"/g, '');
fs.writeFileSync('includes/components/dashboard_projects_activity.php', c2);

let c3 = fs.readFileSync('includes/components/dashboard_tasks.php', 'utf8');
c3 = c3.replace(/onclick="window\.location\.href='\/pages\/tasks\.php\?task_id=<\?php echo htmlspecialchars\(\$t\['id'\], ENT_QUOTES, 'UTF-8'\); \?>'"/g, '');
fs.writeFileSync('includes/components/dashboard_tasks.php', c3);
