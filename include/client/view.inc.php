<?php
if(!defined('OSTCLIENTINC') || !$thisclient || !$ticket || !$ticket->checkUserAccess($thisclient)) die('Access Denied!');

$info=($_POST && $errors)?Format::htmlchars($_POST):array();

$type = array('type' => 'viewed');
Signal::send('object.view', $ticket, $type);

$dept = $ticket->getDept();
$status = $ticket->getStatus();
$statusState = $ticket->isClosed() ? 'closed' : 'open';
$statusName = $status ? $status->getLocalName() : '';
$statusSlug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($statusName)), '-');
$statusDetails = array(
    'new' => __('Received and awaiting triage'),
    'open' => __('Queued with the support team'),
    'in-progress' => __('Actively being worked on'),
    'awaiting-customer' => __('Waiting for your response'),
    'escalated' => __('Escalated for priority attention'),
    'resolved' => __('A solution has been provided'),
    'closed' => __('This request is complete'),
);
$statusDetail = $statusDetails[$statusSlug] ?: ($ticket->isClosed()
    ? __('Closed')
    : ($ticket->isAnswered() ? __('Awaiting your reply') : __('Awaiting support')));

if ($ticket->isClosed() && !$ticket->isReopenable())
    $warn = sprintf(__('%s is marked as closed and cannot be reopened.'), __('This ticket'));

//Making sure we don't leak out internal dept names
if(!$dept || !$dept->isPublic())
    $dept = $cfg->getDefaultDept();

if ($thisclient && $thisclient->isGuest()
    && $cfg->isClientRegistrationEnabled()) { ?>

<div id="msg_info">
    <i class="icon-compass icon-2x pull-left"></i>
    <strong><?php echo __('Looking for your other tickets?'); ?></strong><br />
    <a href="<?php echo ROOT_PATH; ?>login.php?e=<?php
        echo urlencode($thisclient->getEmail());
    ?>" style="text-decoration:underline"><?php echo __('Sign In'); ?></a>
    <?php echo sprintf(__('or %s register for an account %s for the best experience on our help desk.'),
        '<a href="account.php?do=create" style="text-decoration:underline">','</a>'); ?>
    </div>

<?php } ?>

<table width="800" cellpadding="1" cellspacing="0" border="0" id="ticketInfo">
    <tr>
        <td colspan="2" width="100%">
            <h1>
                <a href="tickets.php?id=<?php echo $ticket->getId(); ?>" title="<?php echo __('Reload'); ?>"><i class="refresh icon-refresh"></i></a>
                <b>
                <?php $subject_field = TicketForm::getInstance()->getField('subject');
                    echo $subject_field->display($ticket->getSubject()); ?>
                </b>
                <small>#<?php echo $ticket->getNumber(); ?></small>
<div class="pull-right">
      <a class="action-button" href="tickets.php?a=print&id=<?php
          echo $ticket->getId(); ?>"><i class="icon-print"></i> <?php echo __('Print'); ?></a>

<?php if ($ticket->hasClientEditableFields()
        // Only ticket owners can edit the ticket details (and other forms)
        && $thisclient->getId() == $ticket->getUserId()) { ?>
                <a class="action-button" href="tickets.php?a=edit&id=<?php
                     echo $ticket->getId(); ?>"><i class="icon-edit"></i> <?php echo __('Edit'); ?></a>
<?php } ?>
<?php if (!$thisclient->isGuest()
        && $thisclient->getId() == $ticket->getUserId()) { ?>
    <form class="ticket-status-action" method="post"
        action="tickets.php?id=<?php echo $ticket->getId(); ?>"
        aria-label="<?php echo $ticket->isClosed()
            ? __('Reopen this ticket') : __('Close this ticket'); ?>">
        <?php csrf_token(); ?>
        <input type="hidden" name="id" value="<?php echo $ticket->getId(); ?>">
        <input type="hidden" name="a" value="<?php echo $ticket->isClosed() ? 'reopen' : 'close'; ?>">
        <?php if (!$ticket->isClosed() || $ticket->isReopenable()) { ?>
        <button type="submit" class="action-button status-action-<?php
            echo $ticket->isClosed() ? 'reopen' : 'close'; ?>"
            data-confirm="<?php echo Format::htmlchars($ticket->isClosed()
                ? __('Reopen this ticket?')
                : __('Close this ticket? You can reopen it later if permitted.')); ?>">
            <i class="<?php echo $ticket->isClosed() ? 'icon-undo' : 'icon-ok'; ?>" aria-hidden="true"></i>
            <?php echo $ticket->isClosed() ? __('Reopen') : __('Close'); ?>
        </button>
        <?php } ?>
    </form>
<?php } ?>
</div>
            </h1>
        </td>
    </tr>
    <tr>
        <td width="50%">
            <table class="infoTable" cellspacing="1" cellpadding="3" width="100%" border="0">
                <thead>
                    <tr><td class="headline" colspan="2">
                        <?php echo __('Basic Ticket Information'); ?>
                    </td></tr>
                </thead>
                <tr>
                    <th width="100"><?php echo __('Ticket Status');?>:</th>
                    <td>
                        <span class="ticket-status ticket-status-<?php echo $statusState; ?> ticket-status-<?php echo Format::htmlchars($statusSlug); ?>">
                            <span class="status-dot" aria-hidden="true"></span>
                            <?php echo $status ? Format::htmlchars($status->getLocalName()) : ''; ?>
                        </span>
                        <span class="ticket-status-detail"><?php echo $statusDetail; ?></span>
                    </td>
                </tr>
                <tr>
                    <th><?php echo __('Department');?>:</th>
                    <td><?php echo Format::htmlchars($dept instanceof Dept ? $dept->getName() : ''); ?></td>
                </tr>
                <tr>
                    <th><?php echo __('Create Date');?>:</th>
                    <td><?php echo Format::datetime($ticket->getCreateDate()); ?></td>
                </tr>
           </table>
       </td>
       <td width="50%">
           <table class="infoTable" cellspacing="1" cellpadding="3" width="100%" border="0">
                <thead>
                    <tr><td class="headline" colspan="2">
                        <?php echo __('User Information'); ?>
                    </td></tr>
                </thead>
               <tr>
                   <th width="100"><?php echo __('Name');?>:</th>
                   <td><?php echo mb_convert_case(Format::htmlchars($ticket->getName()), MB_CASE_TITLE); ?></td>
               </tr>
               <tr>
                   <th width="100"><?php echo __('Email');?>:</th>
                   <td><?php echo Format::htmlchars($ticket->getEmail()); ?></td>
               </tr>
               <tr>
                   <th><?php echo __('Phone');?>:</th>
                   <td><?php echo $ticket->getPhoneNumber(); ?></td>
               </tr>
            </table>
       </td>
    </tr>
    <tr>
        <td colspan="2">
<!-- Custom Data -->
<?php
$sections = $forms = array();
foreach (DynamicFormEntry::forTicket($ticket->getId()) as $i=>$form) {
    // Skip core fields shown earlier in the ticket view
    $answers = $form->getAnswers()->exclude(Q::any(array(
        'field__flags__hasbit' => DynamicFormField::FLAG_EXT_STORED,
        'field__name__in' => array('subject', 'priority'),
        Q::not(array('field__flags__hasbit' => DynamicFormField::FLAG_CLIENT_VIEW)),
    )));
    // Skip display of forms without any answers
    foreach ($answers as $j=>$a) {
        if ($v = $a->display())
            $sections[$i][$j] = array($v, $a);
    }
    // Set form titles
    $forms[$i] = $form->getTitle();
}
foreach ($sections as $i=>$answers) {
    ?>
        <table class="custom-data" cellspacing="0" cellpadding="4" width="100%" border="0">
        <tr><td colspan="2" class="headline flush-left"><?php echo $forms[$i]; ?></th></tr>
<?php foreach ($answers as $A) {
    list($v, $a) = $A; ?>
        <tr>
            <th><?php
echo $a->getField()->get('label');
            ?>:</th>
            <td><?php
echo $v;
            ?></td>
        </tr>
<?php } ?>
        </table>
    <?php
} ?>
    </td>
</tr>
</table>
<br>
  <?php
    $email = $thisclient->getUserName();
    $clientId = TicketUser::lookupByEmail($email)->getId();

    $ticket->getThread()->render(array('M', 'R', 'user_id' => $clientId), array(
                    'mode' => Thread::MODE_CLIENT,
                    'html-id' => 'ticketThread')
                );
if ($blockReply = $ticket->isChild() && $ticket->getMergeType() != 'visual')
    $warn = sprintf(__('This Ticket is Merged into another Ticket. Please go to the %s%d%s to reply.'),
        '<a href="tickets.php?id=', $ticket->getPid(), '" style="text-decoration:underline">Parent</a>');
  ?>

<div class="clear" style="padding-bottom:10px;"></div>
<?php if ($ticket->isClosed() && !$thisclient->isGuest()
        && $thisclient->getId() == $ticket->getUserId()) { ?>
<section class="ticket-feedback" aria-labelledby="feedback-heading">
    <h2 id="feedback-heading"><?php echo __('How was your support experience?'); ?></h2>
<?php if ($ticket->hasSatisfactionScore()) {
    $score = $ticket->getSatisfactionScore(); ?>
    <p class="feedback-result" role="status"><span aria-hidden="true"><?php
        echo str_repeat('★', $score).str_repeat('☆', 5 - $score); ?></span>
        <strong><?php echo sprintf(__('%d out of 5'), $score); ?></strong><br>
        <?php echo __('Thank you for your feedback.'); ?></p>
<?php } else { ?>
    <form method="post" action="tickets.php?id=<?php echo $ticket->getId(); ?>#feedback-heading">
        <?php csrf_token(); ?>
        <input type="hidden" name="a" value="feedback">
        <fieldset>
            <legend><?php echo __('Rate this ticket from 1 to 5'); ?></legend>
            <div class="feedback-stars">
<?php foreach (array(1 => __('Very poor'), __('Poor'), __('Average'), __('Good'), __('Excellent')) as $value => $label) { ?>
                <input type="radio" id="feedback-<?php echo $value; ?>" name="score"
                    value="<?php echo $value; ?>" required>
                <label for="feedback-<?php echo $value; ?>" title="<?php echo Format::htmlchars($label); ?>">
                    <span aria-hidden="true">★</span><span class="sr-only"><?php
                        echo sprintf(__('%d - %s'), $value, $label); ?></span>
                </label>
<?php } ?>
            </div>
        </fieldset>
        <button class="button primary" type="submit"><?php echo __('Submit Rating'); ?></button>
    </form>
<?php } ?>
</section>
<?php } ?>
<?php if($errors['err']) { ?>
    <div id="msg_error" role="alert"><?php echo $errors['err']; ?></div>
<?php }elseif($msg) { ?>
    <div id="msg_notice" role="status"><?php echo $msg; ?></div>
<?php }elseif($warn) { ?>
    <div id="msg_warning" role="status"><?php echo $warn; ?></div>
<?php }
if ((!$ticket->isClosed() || $ticket->isReopenable()) && !$blockReply) { ?>
<form id="reply" action="tickets.php?id=<?php echo $ticket->getId();
?>#reply" name="reply" method="post" enctype="multipart/form-data">
    <?php csrf_token(); ?>
    <h2><?php echo __('Post a Reply');?></h2>
    <input type="hidden" name="id" value="<?php echo $ticket->getId(); ?>">
    <input type="hidden" name="a" value="reply">
    <div>
        <p><em><?php
         echo __('To best assist you, we request that you be specific and detailed'); ?></em>
        <font class="error">*&nbsp;<?php echo $errors['message']; ?></font>
        </p>
        <textarea name="<?php echo $messageField->getFormName(); ?>" id="message" cols="50" rows="9" wrap="soft"
            class="<?php if ($cfg->isRichTextEnabled()) echo 'richtext';
                ?> draft" <?php
list($draft, $attrs) = Draft::getDraftAndDataAttrs('ticket.client', $ticket->getId(), $info['message']);
echo $attrs; ?>><?php echo $draft ?: $info['message'];
            ?></textarea>
    <?php
    if ($messageField->isAttachmentsEnabled()) {
        print $attachments->render(array('client'=>true));
    } ?>
    </div>
<?php
  if ($ticket->isClosed() && $ticket->isReopenable()) { ?>
    <div class="warning-banner">
        <?php echo __('Ticket will be reopened on message post'); ?>
    </div>
<?php } ?>
    <p style="text-align:center">
        <input type="submit" value="<?php echo __('Post Reply');?>">
        <input type="reset" value="<?php echo __('Reset');?>">
        <input type="button" value="<?php echo __('Cancel');?>" onClick="history.go(-1)">
    </p>
</form>
<?php
} ?>
<script type="text/javascript">
<?php
// Hover support for all inline images
$urls = array();
foreach (AttachmentFile::objects()->filter(array(
    'attachments__thread_entry__thread__id' => $ticket->getThreadId(),
    'attachments__inline' => true,
)) as $file) {
    $urls[strtolower($file->getKey())] = array(
        'download_url' => $file->getDownloadUrl(['type' => 'H']),
        'filename' => $file->name,
    );
} ?>
showImagesInline(<?php echo JsonDataEncoder::encode($urls); ?>);
</script>
<script>
$(function() {
    $('.ticket-status-action button[data-confirm]').on('click', function(event) {
        if (!window.confirm($(this).attr('data-confirm')))
            event.preventDefault();
    });
});
</script>
