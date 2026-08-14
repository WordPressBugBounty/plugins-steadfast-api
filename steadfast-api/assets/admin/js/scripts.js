/**
 * Admin Scripts
 */

jQuery(document).ready(function () {

    var check = jQuery("#stdf_settings_tab_checkbox").is(':checked'),
        disableFieldCss = { 'pointer-events': 'none', 'border': '1px solid lightcyan', 'color': 'lightgray' };

    if (check === true) {
        jQuery('#api_settings_tab_api_secret_key').removeAttr('style');
        jQuery('#api_settings_tab_api_key').removeAttr('style');
    } else {
        jQuery('#api_settings_tab_api_secret_key').css(disableFieldCss);
        jQuery('#api_settings_tab_api_key').css(disableFieldCss);
    }

    jQuery('#stdf_settings_tab_checkbox').click(function () {

        var thisButton = jQuery(this).is(':checked');

        if (thisButton === true) {
            jQuery('#api_settings_tab_api_secret_key').removeAttr('style');
            jQuery('#api_settings_tab_api_key').removeAttr('style');
        }
        if (thisButton === false) {
            jQuery('#api_settings_tab_api_secret_key').css(disableFieldCss);
            jQuery('#api_settings_tab_api_key').css(disableFieldCss);
        }
    });

    jQuery(document).on('click', '.steadfast_send', function (e) {
        e.preventDefault();
        const thisButton = jQuery(this),
            orderId = thisButton.data('order-id'),
            orderNonce = thisButton.data('stdf-order-nonce');

        thisButton.html('Sending...');

        jQuery.ajax({
            type: 'post',
            url: ajaxurl,
            data: {
                'action': 'send_to_steadfast',
                'order_id': orderId,
                'order_nonce': orderNonce,
            },

            success: function (response) {

                if (response.success) {
                    thisButton.removeClass('steadfast_send').addClass('steadfast-send-success');
                    thisButton.attr('data-is-sent', 'true');
                    thisButton.html('Success').attr('disabled', 'disabled').addClass('tooltip');
                    thisButton.append('<span class="tooltip-text">This parcel is already send to SteadFast!</span>');

                    var $row = thisButton.closest('tr');
                    
                    // 1. Update Consignment ID column
                    $row.find('.column-consignment_id').html('<div class="std-consignment-id">' + response.data.consignment_id + '</div>');
                    
                    // 2. Update Invoice/Print column
                    $row.find('.column-print_details').html(response.data.print_html);
                    
                    // 3. Update Delivery Status column
                    $row.find('.column-delivery_status').html(response.data.delivery_html);
                    
                    // 4. Disable Amount input field
                    $row.find('#steadfast-amount').addClass('amount-disable').attr('disabled', 'disabled');
                } else {
                    if (response.data.message === 'unauthorized') {
                        thisButton.html('Unauthorized').addClass('unauthorized').css({
                            "backgroundColor": "#f35151",
                            "color": "white",
                            "width": "78px",
                            "padding": "2px",
                            "font-size": "11px",
                            "font-family": "sans-serif"
                        });
                    } else {
                        thisButton.html('Failed').addClass('steadfast-failed tooltip');
                        thisButton.append('<span class="tooltip-text">' + response.data.message + '</span>');
                    }
                }
            },
            error: function (xhr, textStatus, errorThrown) {
                console.log('Error:', errorThrown);
                // Handle error here
            }
        });
    });

    jQuery(document).on('focusout', "#steadfast-amount", function () {

        var thisField = jQuery(this),
            inputValue = thisField.val(),
            inputId = thisField.data('order-id'),
            stdAmountNonce = thisField.data("stdf-amount");

        jQuery.ajax({
            type: 'post',
            url: ajaxurl,
            data: {
                "action": "input_amount",
                "input_value": inputValue,
                "input_id": inputId,
                "stdf_amount_nonce": stdAmountNonce,
            },

            success: function (response) {
                if (response.data.message === 'success') {
                    thisField.css({ 'border': '1px solid #5b841b' });
                }
            }
        });
    });

    jQuery(document).on('click', ".std-balance", function () {

        var thisButton = jQuery(this),
            thisButtonVal = thisButton.val(),
            showBalance = jQuery(".std-current-bal"),
            stdBalanceNonce = thisButton.data("stdf-balance-nonce");

        thisButton.html('Checking....');

        jQuery.ajax({
            url: ajaxurl,
            data: {
                "action": "std_current_balance",
                "value": thisButtonVal,
                "stdf_nonce": stdBalanceNonce,
            }, type: 'post',

            success: function (response) {
                if (response.success) {
                    var data = response.data;
                    if ('failed' === data) {
                        thisButton.html('Failed!').css({ 'width': '80px', 'background': '#ff3737', 'border': 'none', 'font-weight': '400', 'pointer-events': 'none' });
                    } else if ('unauthorized' === data) {
                        thisButton.html('Unauthorized').css({ 'width': '99px', 'border': 'none', 'background': '#fb3c3ca8', 'font-weight': '400', 'pointer-events': 'none' });
                    } else {
                        showBalance.find(".balance").html(data + " TK");
                        showBalance.removeClass("hidden");
                        thisButton.html('Balance').css({ 'background': '#3f9668', 'border': 'none', 'color': 'white', 'pointer-events': 'none' });
                    }
                }
            }
        });
    });


    jQuery(document).on('click', "#std-delivery-status", function (e) {
        e.preventDefault();

        var thisButton = jQuery(this),
            consignmentID = thisButton.data("consignment-id"),
            orderID = thisButton.data("order-id"),
            stdNonce = thisButton.data("stdf-status"),
            statusButton = thisButton.siblings('span');

        thisButton.html('Checking...');

        jQuery.ajax({
            url: ajaxurl,
            data: {
                "action": "stdf_delivery_status",
                "consignment_id": consignmentID,
                "order_id": orderID,
                "stdf_nonce": stdNonce,
            }, type: 'post',

            success: function (response) {

                if (response.success) {
                    var message = response.data;

                    thisButton.addClass('hidden');
                    thisButton.siblings('div').removeClass('hidden');

                    if ('unauthorized' === message) {
                        statusButton.removeClass('hidden').html(message).addClass('std-unauthorized');
                    }

                    if ('unauthorized' === message) {
                        statusButton.removeClass('hidden').html(message).addClass('std-unauthorized');
                    } else if ('in_review' === message) {
                        statusButton.removeClass('hidden').html(message).addClass('std-in-review');
                    } else if ('cancelled' === message) {
                        statusButton.removeClass('hidden').html(message).addClass('std-cancelled');
                    } else if ('pending' === message) {
                        statusButton.removeClass('hidden').html(message).addClass('std-pending');
                    } else if ('delivered_approval_pending' === message) {
                        statusButton.removeClass('hidden').html(message).addClass('std-delivered-approval-pending');
                    } else if ('partial_delivered_approval_pending' === message) {
                        statusButton.removeClass('hidden').html(message).addClass('std-partial-delivered-approval-pending');
                    } else if ('cancelled_approval_pending' === message) {
                        statusButton.removeClass('hidden').html(message).addClass('std-cancelled-approval-pending');
                    } else if ('unknown_approval_pending' === message) {
                        statusButton.removeClass('hidden').html(message).addClass('std-unknown-approval-pending');
                    } else if ('delivered' === message) {
                        statusButton.removeClass('hidden').html(message).addClass('std-delivered');
                    } else if ('partial_delivered' === message) {
                        statusButton.removeClass('hidden').html(message).addClass('std-partial-delivered');
                    } else if ('hold' === message) {
                        statusButton.removeClass('hidden').html(message).addClass('std-in-hold');
                    } else if ('unknown' === message) {
                        statusButton.removeClass('hidden').html(message).addClass('std-in-unknown');
                    }

                }
            }
        });
    });


    jQuery(document).on('click', "#std-re-check-delivery-status", function (e) {

        e.preventDefault();

        var thisButton = jQuery(this),
            consignmentID = thisButton.data("consignment-id"),
            orderID = thisButton.data("order-id"),
            stdNonce = thisButton.data("stdf-status");

        var statusButton = thisButton.siblings('span');
        statusButton.html('Checking..').css({ 'line-height': 'inherit' });

        jQuery.ajax({
            url: ajaxurl,
            data: {
                "action": "stdf_delivery_status",
                "consignment_id": consignmentID,
                "order_id": orderID,
                "stdf_nonce": stdNonce,
            }, type: 'post',

            success: function (response) {

                if (response.success) {

                    var message = response.data;

                    if ('unauthorized' === message) {
                        statusButton.html(message).removeAttr('class').addClass('std-unauthorized');
                    } else if ('in_review' === message) {
                        statusButton.html(message).removeAttr('class').addClass('std-in-review');
                    } else if ('cancelled' === message) {
                        statusButton.html(message).removeAttr('class').addClass('std-cancelled');
                    } else if ('pending' === message) {
                        statusButton.html(message).removeAttr('class').addClass('std-pending');
                    } else if ('delivered_approval_pending' === message) {
                        statusButton.html(message).removeAttr('class').addClass('std-delivered-approval-pending');
                    } else if ('partial_delivered_approval_pending' === message) {
                        statusButton.html(message).removeAttr('class').addClass('std-partial-delivered-approval-pending');
                    } else if ('cancelled_approval_pending' === message) {
                        statusButton.html(message).removeAttr('class').addClass('std-cancelled-approval-pending');
                    } else if ('unknown_approval_pending' === message) {
                        statusButton.html(message).removeAttr('class').addClass('std-unknown-approval-pending');
                    } else if ('delivered' === message) {
                        statusButton.html(message).removeAttr('class').addClass('std-delivered');
                    } else if ('partial_delivered' === message) {
                        statusButton.html(message).removeAttr('class').addClass('std-partial-delivered');
                    } else if ('hold' === message) {
                        statusButton.html(message).removeAttr('class').addClass('std-in-hold');
                    } else if ('unknown' === message) {
                        statusButton.html(message).removeAttr('class').addClass('std-in-unknown');
                    }
                }
            }
        });
    });


    jQuery('.amount-disable').attr('disabled', 'disabled');
    jQuery('.steadfast-send-success').html('Success').attr('disabled', 'disabled').addClass('tooltip');
    jQuery('.tooltip').append('<span class="tooltip-text">This parcel is already send to SteadFast!</span>');
});


// Check Courier Score
jQuery(document).ready(function ($) {
    var $modal = $('#stdf-customer-info-modal');
    var $overlay = $('#stdf-modal-overlay');
    var $closeButton = $('#stdf-close-modal');

    $(document).on('click', '#stdf-courier-score', function (e) {
        e.preventDefault();

        var thisButton = $(this);
        thisButton.find('span').text('Refreshing...');
        var order_id = thisButton.data('order-id');
        var stdfNonce = thisButton.data('courier-score-nonce');
        thisButton.removeClass("stdf-success-ratio");

        jQuery.ajax({
            url: ajaxurl,
            data: {
                "action": "get_order_info",
                "order_id": order_id,
                "stdf_nonce": stdfNonce,
            }, type: 'post',

            success: function(response) {

            
            let content = '';
            let success_ratio = response.data.success_ratio || '0.00%';
            
            if (response.data.message && response.data.message.trim().length > 0) {
                content = `
                    <h2>
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color: var(--std-primary); vertical-align: middle; margin-right: 6px; position: relative; top: -1px;"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Info
                    </h2>
                    <p style="font-size: 13px; color: #718096; margin-bottom: 12px; line-height: 1.4;">${response.data.message}</p>
                    <div class="stdf-modal-row">
                        <span class="stdf-row-label">Attempts Left:</span>
                        <span class="stdf-row-val">${response.data.attempts_left || 0}</span>
                    </div>
                `;
            } 
            else if (response.data.error) {
                content = `
                    <h2>
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color: #e53e3e; vertical-align: middle; margin-right: 6px; position: relative; top: -1px;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        Sorry!
                    </h2>
                    <p style="font-size: 13px; color: #718096; margin-bottom: 12px; line-height: 1.4;">${response.data.error}</p>
                    <div class="stdf-modal-row">
                        <span class="stdf-row-label">Already Checked:</span>
                        <span class="stdf-row-val">${response.data.current || 0}</span>
                    </div>
                    <div class="stdf-modal-row">
                        <span class="stdf-row-label">Your Limit:</span>
                        <span class="stdf-row-val">${response.data.limit || 0}</span>
                    </div>
                `;
            }
            else {
                content = `
                    <h2>
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color: var(--std-primary); vertical-align: middle; margin-right: 6px; position: relative; top: -1px;"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 002 2h2a2 2 0 002-2z"/></svg>
                        SteadFast Success Rate
                    </h2>
                    <div class="stdf-modal-row">
                        <span class="stdf-row-label">
                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color: #718096; vertical-align: middle; margin-right: 6px;"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            Total Orders:
                        </span>
                        <span class="stdf-row-val">${response.data.total_parcels || 0}</span>
                    </div>
                    <div class="stdf-modal-row">
                        <span class="stdf-row-label">
                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color: var(--std-mint); vertical-align: middle; margin-right: 6px;"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Total Delivered:
                        </span>
                        <span class="stdf-row-val">${response.data.total_delivered || 0}</span>
                    </div>
                    <div class="stdf-modal-row">
                        <span class="stdf-row-label">
                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color: #e53e3e; vertical-align: middle; margin-right: 6px;"><path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Total Cancelled:
                        </span>
                        <span class="stdf-row-val">${response.data.total_cancelled || 0}</span>
                    </div>
                    <div class="stdf-ratio-card">
                        <div class="stdf-ratio-header">
                            <span class="stdf-ratio-label">Success Ratio</span>
                            <span class="stdf-ratio-value">${success_ratio}</span>
                        </div>
                        <div class="stdf-ratio-progress-track">
                            <div class="stdf-ratio-progress-bar" style="width: ${success_ratio}"></div>
                        </div>
                    </div>
                `;
            }
            
            $('#stdf-customer-info-content').html(content);
            thisButton.find('span').text(success_ratio);
            thisButton.addClass("stdf-success-ratio");
            
            $modal.show();
            $overlay.show();
        },
            error: function () {
                thisButton.html('Failed');
            },
        });
    });

    $closeButton.on('click', function () {
        $modal.hide();
        $overlay.hide();
    });

    $overlay.on('click', function () {
        $modal.hide();
        $overlay.hide();
    });
});

// AJAX Settings Save Handler
jQuery(document).ready(function ($) {
    $('#std-settings-form').on('submit', function (e) {
        e.preventDefault();

        var $form = $(this);
        var $submitButton = $form.find('input[type="submit"]');
        var $progressContainer = $('#std-settings-progress');
        var $progressBar = $progressContainer.find('.std-progress-bar');
        var $progressText = $progressContainer.find('.std-progress-text');

        // Reset and show progress bar
        $progressContainer.removeClass('hidden').show();
        $progressContainer.removeClass('success error');
        $progressBar.css('width', '0%');
        $progressText.text('Saving settings...');

        $submitButton.val('Saving...').attr('disabled', 'disabled');

        // Animate progress to 80% to show active uploading/saving
        setTimeout(function() {
            $progressBar.css('width', '80%');
        }, 50);

        // Prepare FormData
        var formData = new FormData(this);
        formData.append('action', 'save_steadfast_settings');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function (response) {
                $submitButton.val('Save Changes').removeAttr('disabled');
                
                if (response.success) {
                    $progressBar.css('width', '100%');
                    $progressContainer.addClass('success');
                    $progressText.text(response.data.message || 'Settings saved successfully!');
                    
                    // Update business logo preview if newly uploaded
                    if (response.data.logo_url) {
                        var $img = $form.find('img');
                        if ($img.length > 0) {
                            $img.attr('src', response.data.logo_url);
                        } else {
                            $('<img src="' + response.data.logo_url + '" alt="Uploaded Image" style="max-width: 150px; max-height: 80px;"/>').insertBefore($form.find('#std-settings-progress'));
                        }
                    }

                    // Trigger connection check in background after updating settings
                    if (typeof stdf_verify_api_connection === 'function') {
                        stdf_verify_api_connection();
                    }
                } else {
                    $progressContainer.addClass('error');
                    $progressText.text(response.data.message || 'Failed to save settings.');
                }

                // Auto hide progress bar after 3 seconds
                setTimeout(function() {
                    $progressContainer.fadeOut(500, function() {
                        $(this).addClass('hidden');
                    });
                }, 3000);
            },
            error: function () {
                $submitButton.val('Save Changes').removeAttr('disabled');
                $progressContainer.addClass('error');
                $progressText.text('Server connection error. Failed to save settings.');
                
                setTimeout(function() {
                    $progressContainer.fadeOut(500, function() {
                        $(this).addClass('hidden');
                    });
                }, 3000);
            }
        });
    });
});

// AJAX Dynamic Tab Switcher Handler (No Page Reload)
jQuery(document).ready(function ($) {
    $('.nav-tab-wrapper').on('click', 'a.nav-tab', function (e) {
        var tabId = $(this).attr('data-tab');
        if (!tabId) {
            return;
        }

        e.preventDefault();

        // Update nav active tab state
        $('.nav-tab-wrapper a.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');

        // Toggle panel visibility
        $('.std-tab-panel').addClass('hidden');
        $('#std-tab-content-' + tabId).removeClass('hidden');

        // Dynamically update URL in the address bar without reloading
        if (history.pushState) {
            var newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + "?page=steadfast&tab=" + tabId;
            window.history.pushState({ path: newUrl }, '', newUrl);
        }
    });
});

// Dynamic API Connection Checking (Only Once on load, or when credentials are saved)
var stdf_verify_api_connection;

jQuery(document).ready(function ($) {
    var $badge = $('#std-connection-badge');
    
    stdf_verify_api_connection = function () {
        if (!$badge.length) {
            return;
        }

        // Reset badge to checking state
        $badge.removeClass('active inactive').addClass('checking').text('● Checking Connection...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'stdf_test_connection'
            },
            success: function (response) {
                $badge.removeClass('checking');
                if (response.success) {
                    $badge.addClass('active').text('● ' + response.data.message);
                } else {
                    $badge.addClass('inactive').text('● ' + (response.data.message || 'Connection Inactive'));
                }
            },
            error: function () {
                $badge.removeClass('checking').addClass('inactive').text('● Connection Error');
            }
        });
    };

    // Auto-run once on page load if dashboard is active
    if ($badge.length) {
        stdf_verify_api_connection();
    }
});

// Credentials View/Hide Toggle Handler
jQuery(document).ready(function ($) {
    $(document).on('click', '.std-password-toggle', function () {
        var $toggle = $(this);
        var $input = $toggle.siblings('input');
        var $eyeOpen = $toggle.find('.eye-open');
        var $eyeClosed = $toggle.find('.eye-closed');

        if ($input.attr('type') === 'password') {
            $input.attr('type', 'text');
            $eyeOpen.addClass('hidden');
            $eyeClosed.removeClass('hidden');
        } else {
            $input.attr('type', 'password');
            $eyeOpen.removeClass('hidden');
            $eyeClosed.addClass('hidden');
        }
    });
});
