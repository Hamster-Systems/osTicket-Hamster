(function($) {
    'use strict';

    var form, steps, current = 0, enabled = false;

    function titleFor(row, index) {
        return row.attr('data-step-title') || (index === 0
            ? 'Your details' : 'Ticket details');
    }

    function buildSteps() {
        var groups = [], first = [];
        var table = form.children('table');

        table.children('tbody').not('#dynamic-form').each(function() {
            $(this).children('tr').each(function() {
                first.push(this);
            });
        });
        groups.push({ title: 'Your details', rows: first });

        var active = null;
        $('#dynamic-form').children('tr').each(function() {
            var row = $(this);
            if (row.hasClass('portal-form-step')) {
                active = { title: titleFor(row, groups.length), rows: [] };
                groups.push(active);
            }
            if (!active) {
                active = { title: 'Ticket details', rows: [] };
                groups.push(active);
            }
            active.rows.push(this);
        });
        return groups.filter(function(group) { return group.rows.length; });
    }

    function validateStep() {
        var invalid = null;
        $(steps[current].rows).find(':input').each(function() {
            if (!invalid && this.checkValidity && !this.checkValidity())
                invalid = this;
        });
        if (invalid) {
            invalid.reportValidity();
            invalid.focus();
            return false;
        }
        return true;
    }

    function render() {
        form.find('[data-wizard-step]').attr('hidden', true);
        $(steps[current].rows).removeAttr('hidden');

        var progress = form.find('.ticket-wizard-progress li');
        progress.removeClass('is-active is-complete').removeAttr('aria-current');
        progress.each(function(index) {
            if (index < current) $(this).addClass('is-complete');
            if (index === current)
                $(this).addClass('is-active').attr('aria-current', 'step');
        });

        form.find('.wizard-back').prop('hidden', current === 0);
        form.find('.wizard-next').prop('hidden', current === steps.length - 1);
        form.find('.buttons input[type=submit]').prop('hidden',
            current !== steps.length - 1);
        form.find('.wizard-step-heading').text(steps[current].title);
    }

    function setup(useWizard) {
        form = $('#ticketForm');
        if (!form.length) return;

        form.find('.ticket-wizard,.wizard-back,.wizard-next,.wizard-step-heading').remove();
        form.find('.buttons').removeClass('wizard-actions').off('.portalWizard');
        form.removeClass('is-wizard');
        form.find('[data-wizard-step]').removeAttr('data-wizard-step hidden');
        form.find('.buttons input').prop('hidden', false);
        enabled = !!useWizard;
        if (!enabled) return;

        steps = buildSteps();
        if (steps.length < 2) return;
        current = 0;
        form.addClass('is-wizard');

        $.each(steps, function(index, step) {
            $(step.rows).attr('data-wizard-step', index);
        });

        var wizard = $('<div class="ticket-wizard" aria-label="Ticket form progress">' +
            '<ol class="ticket-wizard-progress"></ol></div>');
        $.each(steps, function(index, step) {
            wizard.find('ol').append($('<li><span></span></li>')
                .find('span').text(step.title).end());
        });
        form.prepend(wizard);
        form.children('table').before('<h2 class="wizard-step-heading" tabindex="-1"></h2>');

        var buttons = form.find('.buttons');
        buttons.addClass('wizard-actions')
            .prepend('<button type="button" class="wizard-next">Continue</button>')
            .prepend('<button type="button" class="wizard-back">Back</button>');

        buttons.on('click.portalWizard', '.wizard-back', function() {
            if (current > 0) {
                current--;
                render();
                form.find('.wizard-step-heading').focus();
            }
        }).on('click.portalWizard', '.wizard-next', function() {
            if (validateStep() && current < steps.length - 1) {
                current++;
                render();
                form.find('.wizard-step-heading').focus();
            }
        });

        var errorStep = form.find('[data-wizard-step]').filter(function() {
            return $(this).find('.error').filter(function() {
                return $.trim($(this).text()).replace('*', '').length;
            }).length;
        }).first().attr('data-wizard-step');
        if (errorStep !== undefined) current = parseInt(errorStep, 10) || 0;
        render();
    }

    window.PortalTicketWizard = {
        refresh: setup
    };

    $(function() {
        setup(!!window.portalTicketWizardEnabled);
    });
})(jQuery);
