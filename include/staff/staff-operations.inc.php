<?php
if (!defined('OSTSTAFFINC') || !$thisstaff)
    die('Access Denied');

$staffIds = array($thisstaff->getId());
if ($thisstaff->hasPerm(ReportModel::PERM_AGENTS)) {
    $deptIds = array_values(array_filter(array_map('intval', $thisstaff->getDepts())));
    if ($deptIds) {
        $visible = db_query('SELECT staff_id FROM '.STAFF_TABLE
            .' WHERE isactive=1 AND dept_id IN ('.implode(',', $deptIds).')');
        while ($row = db_fetch_row($visible))
            $staffIds[] = $row[0];
    }
}
$staffIds = array_values(array_filter(array_map('intval', $staffIds)));
$idList = implode(',', $staffIds ?: array(0));

$agents = array();
$res = db_query('SELECT s.staff_id,s.firstname,s.lastname,d.name department '
    .'FROM '.STAFF_TABLE.' s LEFT JOIN '.DEPT_TABLE.' d ON d.id=s.dept_id '
    .'WHERE s.staff_id IN ('.$idList.') AND s.isactive=1 ORDER BY s.firstname,s.lastname');
while ($row = db_fetch_array($res)) {
    $row += array('open'=>0,'overdue'=>0,'unanswered'=>0,'urgent'=>0,
        'closed'=>0,'reopened'=>0,'avg_resolution'=>0,'replies'=>0);
    $agents[$row['staff_id']] = $row;
}
$res = db_query('SELECT t.staff_id,COUNT(*) open,SUM(t.isoverdue=1) overdue,'
    .'SUM(t.isanswered=0) unanswered,SUM((t.flags & 96)>0) urgent '
    .'FROM '.TICKET_TABLE.' t JOIN '.TICKET_STATUS_TABLE.' s ON s.id=t.status_id '
    .'WHERE t.staff_id IN ('.$idList.') AND s.state="open" GROUP BY t.staff_id');
while ($row = db_fetch_array($res))
    if (isset($agents[$row['staff_id']]))
        $agents[$row['staff_id']] = array_merge($agents[$row['staff_id']], $row);

$res = db_query('SELECT staff_id,COUNT(*) closed,'
    .'ROUND(AVG(TIMESTAMPDIFF(HOUR,created,closed)),1) avg_resolution '
    .'FROM '.TICKET_TABLE.' WHERE staff_id IN ('.$idList.') '
    .'AND closed>=DATE_SUB(NOW(),INTERVAL 30 DAY) GROUP BY staff_id');
while ($row = db_fetch_array($res))
    if (isset($agents[$row['staff_id']]))
        $agents[$row['staff_id']] = array_merge($agents[$row['staff_id']], $row);

$events = Event::getIds();
if ($reopenedId = (int) @$events['reopened']) {
    $res = db_query('SELECT staff_id,COUNT(*) reopened FROM '.THREAD_EVENT_TABLE
        .' WHERE staff_id IN ('.$idList.') AND event_id='.$reopenedId
        .' AND annulled=0 AND timestamp>=DATE_SUB(NOW(),INTERVAL 30 DAY) GROUP BY staff_id');
    while ($row = db_fetch_array($res))
        if (isset($agents[$row['staff_id']]))
            $agents[$row['staff_id']]['reopened'] = $row['reopened'];
}
$res = db_query('SELECT staff_id,COUNT(*) replies FROM '.THREAD_ENTRY_TABLE
    .' WHERE staff_id IN ('.$idList.') AND type="R" '
    .'AND created>=DATE_SUB(NOW(),INTERVAL 30 DAY) GROUP BY staff_id');
while ($row = db_fetch_array($res))
    if (isset($agents[$row['staff_id']]))
        $agents[$row['staff_id']]['replies'] = $row['replies'];

$defaults = array();
foreach (array('mon','tue','wed','thu','fri','sat','sun') as $day)
    $defaults[$day] = array('enabled'=>!in_array($day,array('sat','sun')),
        'start'=>'09:00','end'=>'17:00');
$saved = json_decode($staff->get('work_schedule', ''), true);
$hours = is_array($saved) ? $saved + $defaults : $defaults;
$availability = $staff->get('availability_status', 'available');
$until = $staff->get('availability_until', '');
$statuses = array('available'=>__('Available'),'busy'=>__('Busy'),
    'away'=>__('Away'),'offline'=>__('Offline'));
?>
<div class="staff-ops-heading"><div><h1><?php echo __('Staff Operations'); ?></h1>
<p><?php echo __('Live workload, recent performance, and working availability.'); ?></p></div>
<span class="ops-period"><?php echo __('Performance: last 30 days'); ?></span></div>
<ul class="clean tabs staff-ops-tabs">
<li class="active"><a href="#ops-workload"><?php echo __('Workload'); ?></a></li>
<li><a href="#ops-performance"><?php echo __('Performance'); ?></a></li>
<li><a href="#ops-schedule"><?php echo __('My Schedule'); ?></a></li></ul>

<div class="tab_content" id="ops-workload">
<div class="ops-summary">
<div><strong><?php echo array_sum(array_column($agents,'open')); ?></strong><span><?php echo __('Open tickets'); ?></span></div>
<div><strong><?php echo array_sum(array_column($agents,'overdue')); ?></strong><span><?php echo __('Overdue'); ?></span></div>
<div><strong><?php echo array_sum(array_column($agents,'unanswered')); ?></strong><span><?php echo __('Awaiting response'); ?></span></div>
<div><strong><?php echo count($agents); ?></strong><span><?php echo __('Agents shown'); ?></span></div></div>
<table class="list staff-ops-table"><thead><tr><th><?php echo __('Agent'); ?></th><th><?php echo __('Availability'); ?></th><th><?php echo __('Open'); ?></th><th><?php echo __('Overdue'); ?></th><th><?php echo __('Awaiting reply'); ?></th><th><?php echo __('SEV-1 / SEV-2'); ?></th><th><?php echo __('Load'); ?></th></tr></thead><tbody>
<?php foreach ($agents as $agent) { $a=Staff::lookup($agent['staff_id']);
$status=$a->get('availability_status','available');
if (!$a->isAvailable() && $status=='available') $status='away';
$load=min(100, $agent['open']*5+$agent['overdue']*15+$agent['urgent']*20); ?>
<tr><td><strong><?php echo Format::htmlchars(trim($agent['firstname'].' '.$agent['lastname'])); ?></strong><small><?php echo Format::htmlchars($agent['department']); ?></small></td>
<td><span class="availability availability-<?php echo $status; ?>"><?php echo $statuses[$status]; ?></span></td>
<td><?php echo (int)$agent['open']; ?></td><td><?php echo (int)$agent['overdue']; ?></td><td><?php echo (int)$agent['unanswered']; ?></td><td><?php echo (int)$agent['urgent']; ?></td>
<td><div class="load-meter"><i style="width:<?php echo $load; ?>%"></i></div><small><?php echo $load; ?>%</small></td></tr><?php } ?>
</tbody></table></div>

<div class="tab_content hidden" id="ops-performance">
<p class="ops-note"><?php echo __('Statistics include workload context and should not be treated as a standalone ranking.'); ?></p>
<table class="list staff-ops-table"><thead><tr><th><?php echo __('Agent'); ?></th><th><?php echo __('Resolved'); ?></th><th><?php echo __('Replies'); ?></th><th><?php echo __('Average resolution'); ?></th><th><?php echo __('Reopened'); ?></th><th><?php echo __('Reopen rate'); ?></th></tr></thead><tbody>
<?php foreach ($agents as $agent) { $rate=$agent['closed'] ? round($agent['reopened']/$agent['closed']*100,1) : 0; ?>
<tr><td><strong><?php echo Format::htmlchars(trim($agent['firstname'].' '.$agent['lastname'])); ?></strong><small><?php echo Format::htmlchars($agent['department']); ?></small></td><td><?php echo (int)$agent['closed']; ?></td><td><?php echo (int)$agent['replies']; ?></td><td><?php echo $agent['avg_resolution'] ? Format::htmlchars($agent['avg_resolution']).__(' hrs') : '&mdash;'; ?></td><td><?php echo (int)$agent['reopened']; ?></td><td><?php echo $rate; ?>%</td></tr><?php } ?>
</tbody></table></div>

<div class="tab_content hidden" id="ops-schedule">
<form method="post" action="staff-operations.php#ops-schedule" class="ops-schedule-form">
<?php echo csrf_token(); ?><input type="hidden" name="staff_id" value="<?php echo $thisstaff->getId(); ?>"><input type="hidden" name="save_schedule" value="1">
<fieldset><legend><?php echo __('Current availability'); ?></legend>
<label><?php echo __('Status'); ?><select name="availability_status"><?php foreach ($statuses as $value=>$label) { ?><option value="<?php echo $value; ?>" <?php if ($availability==$value) echo 'selected'; ?>><?php echo $label; ?></option><?php } ?></select></label>
<label><?php echo __('Until (optional)'); ?><input type="text" class="dp" name="availability_until" value="<?php echo Format::htmlchars($until); ?>" placeholder="YYYY-MM-DD"></label></fieldset>
<fieldset><legend><?php echo __('Regular working hours'); ?></legend><div class="schedule-grid">
<?php $labels=array('mon'=>__('Monday'),'tue'=>__('Tuesday'),'wed'=>__('Wednesday'),'thu'=>__('Thursday'),'fri'=>__('Friday'),'sat'=>__('Saturday'),'sun'=>__('Sunday')); foreach ($labels as $day=>$label) { $h=$hours[$day]; ?>
<label class="schedule-day"><span><input type="checkbox" name="work[<?php echo $day; ?>][enabled]" value="1" <?php if ($h['enabled']) echo 'checked'; ?>> <?php echo $label; ?></span><input type="time" name="work[<?php echo $day; ?>][start]" value="<?php echo Format::htmlchars($h['start']); ?>"><em>&ndash;</em><input type="time" name="work[<?php echo $day; ?>][end]" value="<?php echo Format::htmlchars($h['end']); ?>"></label><?php } ?>
</div></fieldset><p><button type="submit" class="button green"><?php echo __('Save availability'); ?></button></p>
</form></div>
