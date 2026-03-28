/**
 * Impact Websites – Roof Estimate & Quote
 * Frontend calculator + AJAX submission.
 *
 * All configurable values are passed via wp_localize_script as `irreqData`.
 */
/* global irreqData, jQuery, turnstile */
(function ($) {
  'use strict';

  var data      = irreqData;
  var lastLow   = null;
  var lastHigh  = null;

  /* ── DOM references ──────────────────────────────────────── */
  var $wrap          = $('#irreqFormWrap');
  var $sizeInput     = $wrap.find('#rrRoofSize');
  var $materialSel   = $wrap.find('#rrRoofMaterial');
  var $conditionSel  = $wrap.find('#rrRoofCondition');
  var $serviceSel    = $wrap.find('#rrService');
  var $estimateText  = $wrap.find('#rrEstimateText');
  var $nameInput     = $wrap.find('#rrName');
  var $phoneInput    = $wrap.find('#rrPhone');
  var $emailInput    = $wrap.find('#rrEmail');
  var $addressInput  = $wrap.find('#rrAddress');
  var $suburbInput   = $wrap.find('#rrSuburb');
  var $detailsInput  = $wrap.find('#rrDetails');
  var $submitBtn     = $wrap.find('#irreqSubmitBtn');
  var $formMsg       = $wrap.find('#irreqFormMsg');
  var originalBtnText = $.trim($submitBtn.text());

  /* ── Utilities ───────────────────────────────────────────── */
  function formatNZD(value) {
    return 'NZ$' + value.toLocaleString('en-NZ', { maximumFractionDigits: 0 });
  }

  function showMsg(text, isError) {
    $formMsg
      .removeClass('irreq-success irreq-error')
      .addClass(isError ? 'irreq-error' : 'irreq-success')
      .text(text)
      .show();
  }

  /* ── Estimate calculator ─────────────────────────────────── */
  function calculateEstimate() {
    var size      = parseFloat($sizeInput.val());
    var material  = $materialSel.val();
    var condition = $conditionSel.val();

    if (!size || size <= 0 || !material || !condition) {
      $estimateText.text(data.i18n.fillFields);
      lastLow = lastHigh = null;
      return;
    }

    var matMult  = data.materialRates[material]  || 1;
    var condMult = data.conditionRates[condition] || 1;
    var estimate = size * data.baseRate * matMult * condMult;

    if (estimate < data.minJobTotal) {
      estimate = data.minJobTotal;
    }

    lastLow  = Math.round(estimate * 0.9);
    lastHigh = Math.round(estimate * 1.1);

    $estimateText.text(
      formatNZD(lastLow) + ' – ' + formatNZD(lastHigh) +
      ' (online estimate only, subject to on-site inspection).'
    );
  }

  $sizeInput.on('input', calculateEstimate);
  $materialSel.on('change', calculateEstimate);
  $conditionSel.on('change', calculateEstimate);

  /* ── Form submission ─────────────────────────────────────── */
  $submitBtn.on('click', function () {
    $formMsg.hide();

    if (!lastLow || !lastHigh) {
      showMsg(data.i18n.noEstimate, true);
      return;
    }

    var serviceVal = $serviceSel.val();
    if (!serviceVal) {
      showMsg(data.i18n.noService, true);
      return;
    }

    var name  = $.trim($nameInput.val());
    var phone = $.trim($phoneInput.val());
    var email = $.trim($emailInput.val());

    if (!name || !phone || !email) {
      showMsg(data.i18n.noContact, true);
      return;
    }

    if ($addressInput.length && !$.trim($addressInput.val())) {
      showMsg(data.i18n.noAddress, true);
      return;
    }

    var material  = $materialSel.val();
    var condition = $conditionSel.val();
    var size      = $sizeInput.val();
    var address   = $addressInput.length ? $.trim($addressInput.val()) : '';
    var suburb    = $suburbInput.length  ? $.trim($suburbInput.val())  : '';
    var details   = $detailsInput.length ? $.trim($detailsInput.val()) : '';

    var estimateStr = formatNZD(lastLow) + ' – ' + formatNZD(lastHigh);

    // Collect Turnstile token if widget is present.
    var cfToken = '';
    var $tsWidget = $wrap.find('.cf-turnstile [name="cf-turnstile-response"]');
    if ($tsWidget.length) {
      cfToken = $tsWidget.val();
    }

    $submitBtn.prop('disabled', true).text(data.i18n.sending);

    $.post(data.ajaxUrl, {
      action              : 'irreq_submit',
      nonce               : data.nonce,
      name                : name,
      phone               : phone,
      email               : email,
      roof_size           : size,
      material            : material,
      condition           : condition,
      service             : serviceVal,
      address             : address,
      suburb              : suburb,
      details             : details,
      estimate            : estimateStr,
      cf_turnstile_response: cfToken,
    })
    .done(function (response) {
      if (response.success) {
        showMsg(response.data.message, false);
        // Clear the form after success.
        $sizeInput.val('');
        $materialSel.val('');
        $conditionSel.val('');
        $serviceSel.val('');
        $nameInput.val('');
        $phoneInput.val('');
        $emailInput.val('');
        if ($addressInput.length) $addressInput.val('');
        if ($suburbInput.length)  $suburbInput.val('');
        if ($detailsInput.length) $detailsInput.val('');
        lastLow = lastHigh = null;
        $estimateText.text(data.i18n.fillFields);

        // Reset Turnstile widget if available.
        if (typeof turnstile !== 'undefined') {
          turnstile.reset();
        }
      } else {
        var msg = (response.data && response.data.message)
          ? response.data.message
          : data.i18n.error;
        showMsg(msg, true);
      }
    })
    .fail(function () {
      showMsg(data.i18n.error, true);
    })
    .always(function () {
      $submitBtn.prop('disabled', false).text(originalBtnText);
    });
  });

}(jQuery));
