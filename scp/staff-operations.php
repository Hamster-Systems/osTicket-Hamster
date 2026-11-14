<?php
require('staff.inc.php');
require_once INCLUDE_DIR.'class.report.php';
$staff = Staff::lookup($thisstaff->getId());
$errors = array();
$msg = '';
if ($_POST && isset($_POST['save_schedule'])) {
    if ((int) $_POST['staff_id'] != $thisstaff->getId())
        $errors['err'] = __('You can only update your own availability.');
    elseif (!in_array(@$_POST['availability_status'], array('available','busy','away','offline')))
        $errors['err'] = __('Select a valid availability status.');
    else {
        $schedule = array();
        foreach (array('mon','tue','wed','thu','fri','sat','sun') as $day) {
            $start = @$_POST['work'][$day]['start'] ?: '09:00';
            $end = @$_POST['work'][$day]['end'] ?: '17:00';
            $enabled = isset($_POST['work'][$day]['enabled']);
            if (!preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $start)
                    || !preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $end)
                    || ($enabled && $start >= $end)) {
                $errors['err'] = __('Working hours must have a valid start and end time.');
                break;
            }
            $schedule[$day] = array('enabled'=>$enabled ? 1 : 0,
                'start'=>$start, 'end'=>$end);
        }
        $until = trim(@$_POST['availability_until']);
        if (!$errors && $until && !strtotime($until))
            $errors['err'] = __('Enter a valid availability end date.');
        if (!$errors) {
            $staff->updateConfig(array('work_schedule'=>JsonDataEncoder::encode($schedule),
                'availability_status'=>$_POST['availability_status'],
                'availability_until'=>$until));
            $msg = __('Working hours and availability updated successfully.');
        }
    }
}
$nav->setTabActive('dashboard');
$ost->addExtraHeader('<link rel="stylesheet" href="css/staff-operations.css">');
$ost->addExtraHeader('<meta name="tip-namespace" content="dashboard.staff_operations" />',
    "$('#content').data('tipNamespace', 'dashboard.staff_operations');");
require(STAFFINC_DIR.'header.inc.php');
require(STAFFINC_DIR.'staff-operations.inc.php');
include(STAFFINC_DIR.'footer.inc.php');
?>
