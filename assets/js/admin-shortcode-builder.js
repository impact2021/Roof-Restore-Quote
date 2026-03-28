/**
 * Impact Websites – Roof Estimate & Quote
 * Admin Shortcode Builder: live shortcode generator + panel toggle.
 */
/* global jQuery */
(function ($) {
  'use strict';

  /* ── Builder panel toggle ────────────────────────────────── */
  $('.irreq-builder-toggle').on('click', function () {
    var $body    = $(this).closest('.irreq-builder-panel').find('.irreq-builder-body');
    var expanded = $(this).attr('aria-expanded') === 'true';
    $body.slideToggle(200);
    $(this).attr('aria-expanded', String(!expanded));
  });

  /* ── Shortcode generation ────────────────────────────────── */
  var boolFields = [
    { id: 'bldShowTitle',          attr: 'show_title',             def: true  },
    { id: 'bldShowEstimate',       attr: 'show_estimate_section',  def: true  },
    { id: 'bldShowContact',        attr: 'show_contact_section',   def: true  },
    { id: 'bldShowRoofSize',       attr: 'show_roof_size',         def: true  },
    { id: 'bldReqRoofSize',        attr: 'require_roof_size',      def: true  },
    { id: 'bldShowRoofMaterial',   attr: 'show_roof_material',     def: true  },
    { id: 'bldReqRoofMaterial',    attr: 'require_roof_material',  def: true  },
    { id: 'bldShowRoofCondition',  attr: 'show_roof_condition',    def: true  },
    { id: 'bldReqRoofCondition',   attr: 'require_roof_condition', def: true  },
    { id: 'bldShowService',        attr: 'show_service',           def: true  },
    { id: 'bldReqService',         attr: 'require_service',        def: true  },
    { id: 'bldShowName',           attr: 'show_name',              def: true  },
    { id: 'bldReqName',            attr: 'require_name',           def: true  },
    { id: 'bldShowPhone',          attr: 'show_phone',             def: true  },
    { id: 'bldReqPhone',           attr: 'require_phone',          def: true  },
    { id: 'bldShowEmail',          attr: 'show_email',             def: true  },
    { id: 'bldReqEmail',           attr: 'require_email',          def: true  },
    { id: 'bldShowAddress',        attr: 'show_address',           def: null  }, // null = omit (use global)
    { id: 'bldReqAddress',         attr: 'require_address',        def: true  },
    { id: 'bldShowSuburb',         attr: 'show_suburb',            def: null  },
    { id: 'bldReqSuburb',          attr: 'require_suburb',         def: false },
    { id: 'bldShowDetails',        attr: 'show_details',           def: null  },
    { id: 'bldReqDetails',         attr: 'require_details',        def: false },
  ];

  function buildShortcode() {
    var parts = ['roof_estimate_quote'];

    // Background colour.
    var bgColor = $('#bldBgColor').val();
    if (bgColor && bgColor !== '#0f1724') {
      parts.push('bg_color="' + bgColor + '"');
      var opacity = parseFloat($('#bldBgOpacity').val());
      if (!isNaN(opacity) && Math.abs(opacity - 1.0) > 0.001) {
        parts.push('bg_opacity="' + opacity.toFixed(2).replace(/\.?0+$/, '') + '"');
      }
    }

    // Boolean / ternary fields.
    var showEstimate = $('#bldShowEstimate').prop('checked');
    var showContact  = $('#bldShowContact').prop('checked');

    boolFields.forEach(function (f) {
      var $el  = $('#' + f.id);
      if (!$el.length) { return; }
      var val  = $el.prop('checked');
      var valStr = val ? '1' : '0';

      // For fields with def===null: always include (user explicitly chose).
      if (f.def === null) {
        parts.push(f.attr + '="' + valStr + '"');
        return;
      }

      // Skip "require" fields when the parent "show" checkbox is off.
      if (f.attr.indexOf('require_') === 0) {
        var showId = f.id.replace('bldReq', 'bldShow');
        var $show  = $('#' + showId);
        if ($show.length && !$show.prop('checked')) {
          return; // field is hidden – require attribute is irrelevant
        }
      }

      // Only include if different from default, OR if section is hidden
      // (so we explicitly signal that to the shortcode).
      var defaultVal = f.def ? '1' : '0';
      if (valStr !== defaultVal) {
        parts.push(f.attr + '="' + valStr + '"');
      }
    });

    return '[' + parts.join(' ') + ']';
  }

  function updateOutput() {
    $('#bldOutput').val(buildShortcode());

    // Grey-out field rows when their parent section is disabled.
    var showEstimate = $('#bldShowEstimate').prop('checked');
    var showContact  = $('#bldShowContact').prop('checked');
    $('.irreq-bld-estimate-row').toggleClass('irreq-bld-row-disabled', !showEstimate);
    $('.irreq-bld-contact-row').toggleClass('irreq-bld-row-disabled', !showContact);

    // Grey-out require checkboxes when the show checkbox is off.
    boolFields.forEach(function (f) {
      if (f.attr.indexOf('require_') !== 0) { return; }
      var showId = f.id.replace('bldReq', 'bldShow');
      var $show  = $('#' + showId);
      var $req   = $('#' + f.id);
      if ($show.length) {
        $req.prop('disabled', !$show.prop('checked'));
      }
    });
  }

  // Opacity slider live label.
  $('#bldBgOpacity').on('input change', function () {
    var v = parseFloat($(this).val());
    $('#bldBgOpacityVal').text(isNaN(v) ? '1' : v.toFixed(2));
    updateOutput();
  });

  // Bind all other controls.
  $('#irreq-shortcode-builder').on('change input', 'input, select', updateOutput);

  // Copy button – prefer Clipboard API, fall back to execCommand.
  $('#bldCopyBtn').on('click', function () {
    var text = $('#bldOutput').val();
    var $btn = $(this);
    function onCopied() {
      $btn.text('Copied!');
      setTimeout(function () { $btn.text('Copy'); }, 2000);
    }
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(onCopied).catch(function () {
        // Fallback.
        $('#bldOutput').select();
        try { document.execCommand('copy'); onCopied(); } catch (e) {}
      });
    } else {
      $('#bldOutput').select();
      try { document.execCommand('copy'); onCopied(); } catch (e) {}
    }
  });

  // Initial render.
  updateOutput();

}(jQuery));
