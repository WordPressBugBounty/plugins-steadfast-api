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
                    thisButton.attr('data-is-sent', 'true');
                    thisButton.html('Sent');
                    setTimeout(function () {
                        location.reload();
                    }, 2000);
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
                    <h2>${response.data.message}</h2>
                    <p><strong>Attempts Left:</strong> ${response.data.attempts_left || 0}</p>
                `;
            } 
            else if (response.data.error) {
                content = `
                    <h2>Sorry!</h2>
                    <p>${response.data.error}</p>
                    <p><strong>Already Checked:</strong> ${response.data.current || 0}</p>
                    <p><strong>Your Limit:</strong> ${response.data.limit || 0}</p>
                `;
            }
            else {
                content = `
                    <h2>📊 SteadFast Success Rate</h2>
                    <p><strong>📦 Total Orders:</strong> ${response.data.total_parcels || 0}</p>
                    <p><strong>✅ Total Delivered:</strong> ${response.data.total_delivered || 0}</p>
                    <p><strong>❌ Total Cancelled:</strong> ${response.data.total_cancelled || 0}</p>
                    <p><strong>📈 Success Ratio:</strong> ${success_ratio}</p>
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
