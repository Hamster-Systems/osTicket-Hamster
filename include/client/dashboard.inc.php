<?php
if (!defined('OSTCLIENTINC') || !$thisclient || !$thisclient->isValid())
    die('Access Denied');
$org_tickets = $thisclient->canSeeOrgTickets();
$dashboard_open = $thisclient->getNumOpenTickets($org_tickets);
$dashboard_closed = $thisclient->getNumClosedTickets($org_tickets);
$dashboard_total = $dashboard_open + $dashboard_closed;
$dashboard_user = User::lookup($thisclient->getId());
$dashboard_group = $dashboard_user ? $dashboard_user->getOrganization() : null;
TicketForm::ensureDynamicDataView();
$dashboard_recent = Ticket::objects()->filter(array("user_id" => $thisclient->getId()))->order_by("-created")->limit(5)->values("ticket_id", "number", "created", "status__state", "status__name", "cdata__subject");
?>
<div class="client-dashboard">
    <header class="dashboard-welcome">
        <div>
            <span class="dashboard-eyebrow"><?php echo __('Your support dashboard'); ?></span>
            <?php if ($dashboard_group) { ?>
            <span class="portal-support-plan">
                <i class="icon-building" aria-hidden="true"></i>
                <?php echo Format::htmlchars($dashboard_group->getName()); ?>
                <strong><?php echo Format::htmlchars($dashboard_group->getSupportTierLabel()); ?></strong>
            </span>
            <?php } ?>
            <h1><?php echo sprintf(__('Welcome back, %s'), Format::htmlchars($thisclient->getName())); ?></h1>
            <p><?php echo __('Track your requests, review recent updates, or start a new conversation with our support team.'); ?></p>
        </div>
        <a class="dashboard-primary-action" href="open.php"><i class="icon-plus" aria-hidden="true"></i> <?php echo __('Open a New Ticket'); ?></a>
    </header>

    <section class="dashboard-stats" aria-label="<?php echo __('Ticket summary'); ?>">
        <a class="dashboard-stat stat-open" href="tickets.php?status=open">
            <span class="dashboard-stat-icon"><i class="icon-inbox" aria-hidden="true"></i></span>
            <span><strong><?php echo (int) $dashboard_open; ?></strong><small><?php echo __('Open tickets'); ?></small></span>
        </a>
        <a class="dashboard-stat stat-closed" href="tickets.php?status=closed">
            <span class="dashboard-stat-icon"><i class="icon-check" aria-hidden="true"></i></span>
            <span><strong><?php echo (int) $dashboard_closed; ?></strong><small><?php echo __('Closed tickets'); ?></small></span>
        </a>
        <a class="dashboard-stat stat-total" href="tickets.php">
            <span class="dashboard-stat-icon"><i class="icon-file-text" aria-hidden="true"></i></span>
            <span><strong><?php echo (int) $dashboard_total; ?></strong><small><?php echo __('Total tickets'); ?></small></span>
        </a>
    </section>

    <div class="dashboard-grid">
        <section class="dashboard-panel dashboard-recent">
            <div class="dashboard-panel-heading">
                <div><span class="dashboard-eyebrow"><?php echo __('Activity'); ?></span><h2><?php echo __('Recent tickets'); ?></h2></div>
                <a href="tickets.php"><?php echo __('View all'); ?> <i class="icon-arrow-right" aria-hidden="true"></i></a>
            </div>
            <?php if ($dashboard_recent && $dashboard_recent->exists(true)) { ?>
            <div class="dashboard-ticket-list">
                <?php foreach ($dashboard_recent as $ticket) { ?>
                <a class="dashboard-ticket" href="tickets.php?id=<?php echo (int) $ticket['ticket_id']; ?>">
                    <span class="dashboard-ticket-main">
                        <strong><?php echo Format::htmlchars($ticket['cdata__subject'] ?: __('Support request')); ?></strong>
                        <small>#<?php echo Format::htmlchars($ticket['number']); ?> &middot; <?php echo Format::date($ticket['created']); ?></small>
                    </span>
                    <span class="dashboard-status status-<?php echo Format::htmlchars(strtolower($ticket['status__state'])); ?>"><?php echo Format::htmlchars($ticket['status__name']); ?></span>
                </a>
                <?php } ?>
            </div>
            <?php } else { ?>
            <div class="dashboard-empty">
                <i class="icon-comments-alt" aria-hidden="true"></i>
                <h3><?php echo __('No tickets yet'); ?></h3>
                <p><?php echo __('When you contact support, your requests will appear here.'); ?></p>
            </div>
            <?php } ?>
        </section>

        <aside class="dashboard-panel dashboard-shortcuts">
            <div class="dashboard-panel-heading"><div><span class="dashboard-eyebrow"><?php echo __('Account'); ?></span><h2><?php echo __('Quick links'); ?></h2></div></div>
            <a href="profile.php"><span><i class="icon-user" aria-hidden="true"></i></span><div><strong><?php echo __('Profile settings'); ?></strong><small><?php echo __('Update your details and password'); ?></small></div><i class="icon-chevron-right" aria-hidden="true"></i></a>
            <a href="tickets.php"><span><i class="icon-list" aria-hidden="true"></i></span><div><strong><?php echo __('All tickets'); ?></strong><small><?php echo __('Search your support history'); ?></small></div><i class="icon-chevron-right" aria-hidden="true"></i></a>
            <a href="open.php"><span><i class="icon-plus" aria-hidden="true"></i></span><div><strong><?php echo __('New request'); ?></strong><small><?php echo __('Contact the support team'); ?></small></div><i class="icon-chevron-right" aria-hidden="true"></i></a>
        </aside>
    </div>
</div>
