        </main>
    </div>
    <div id="footer">
        <div class="footer-hamster-mark"><i class="icon-heart" aria-hidden="true"></i><span>Support by Hamster Systems</span></div>
        <div class="footer-theme-control"><button type="button" class="portal-theme-toggle" id="portal-theme-toggle" aria-label="Switch colour theme" title="Switch colour theme"><i class="icon-moon theme-dark-icon" aria-hidden="true"></i><i class="icon-sun theme-light-icon" aria-hidden="true"></i><span class="theme-label">Dark mode</span></button></div>
        <p><?php echo __('Copyright &copy;'); ?> <?php echo date('Y'); ?> <?php
        echo Format::htmlchars((string) $ost->company ?: 'osTicket.com'); ?> - <?php echo __('All rights reserved.'); ?></p>
        <a id="poweredBy" href="https://osticket.com" target="_blank" rel="noopener"><?php echo __('Helpdesk software - powered by osTicket'); ?><span class="sr-only"> (<?php echo __('opens in a new window'); ?>)</span></a>
    </div>
<div id="overlay"></div>
<div id="loading">
    <h4><?php echo __('Please Wait!');?></h4>
    <p><?php echo __('Please wait... it will take a second!');?></p>
</div>
<?php
if (($lang = Internationalization::getCurrentLanguage()) && $lang != 'en_US') { ?>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>ajax.php/i18n/<?php
        echo $lang; ?>/js"></script>
<?php } ?>
<script type="text/javascript">
    getConfig().resolve(<?php
        include INCLUDE_DIR . 'ajax.config.php';
        $api = new ConfigAjaxAPI();
        print $api->client(false);
    ?>);
</script>
</body>
</html>
