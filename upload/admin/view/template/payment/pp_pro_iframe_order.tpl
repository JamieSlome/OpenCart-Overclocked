<h2><?php echo $text_payment_info; ?></h2>
<table class="form">
  <tr>
    <td><?php echo $text_capture_status; ?></td>
    <td id="capture_status"><?php echo $paypal_order['capture_status']; ?></td>
  </tr>
  <tr>
    <td><?php echo $text_amount_auth; ?></td>
    <td>
      <?php echo $paypal_order['total']; ?>
      <?php if ($paypal_order['capture_status'] != 'Complete') { ?>
        &nbsp;&nbsp;
        <a class="button" onclick="doVoid();" id="button-void"><?php echo $button_void; ?></a>
      <?php } ?>
    </td>
  </tr>
  <tr>
    <td><?php echo $text_amount_captured; ?></td>
    <td id="paypal_captured"><?php echo $paypal_order['captured']; ?></td>
  </tr>
  <tr>
    <td><?php echo $text_amount_refunded; ?></td>
    <td id="paypal_refunded"><?php echo $paypal_order['refunded']; ?></td>
  </tr>
  <?php if ($paypal_order['capture_status'] != 'Complete') { ?>
  <tr class="paypal_capture">
    <td><?php echo $text_capture_amount; ?></td>
    <td>
      <p><input type="checkbox" name="paypal_capture_complete" id="paypal_capture_complete" value="1" />&nbsp;<?php echo $text_complete_capture; ?></p>
      <p>
        <input type="text" size="10" id="paypal_capture_amount" value="<?php echo $paypal_order['remaining']; ?>" />
        <a class="button" onclick="capture();" id="button-capture"><?php echo $button_capture; ?></a>
      </p>
    </td>
  </tr>
  <?php } ?>
  <?php if ($paypal_order['capture_status'] != 'Complete') { ?>
  <tr>
    <td><?php echo $text_reauthorise; ?></td>
    <td><a id="button-reauthorise" onclick="reauthorise();" class="button"><?php echo $button_reauthorise; ?></a></td>
  </tr>
  <?php } ?>
  <tr>
    <td><?php echo $text_transactions; ?>: </td>
    <td>
      <table class="list" id="paypal_transactions">
        <thead>
          <tr>
            <td class="left"><strong><?php echo $column_trans_id; ?></strong></td>
            <td class="left"><strong><?php echo $column_amount; ?></strong></td>
            <td class="left"><strong><?php echo $column_type; ?></strong></td>
            <td class="left"><strong><?php echo $column_status; ?></strong></td>
            <td class="left"><strong><?php echo $column_pending_reason; ?></strong></td>
            <td class="left"><strong><?php echo $column_created; ?></strong></td>
            <td class="left"><strong><?php echo $column_action; ?></strong></td>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($transactions as $transaction) { ?>
          <tr>
            <td class="left"><?php echo $transaction['transaction_id']; ?></td>
            <td class="left"><?php echo $transaction['amount']; ?></td>
            <td class="left"><?php echo $transaction['payment_type']; ?></td>
            <td class="left"><?php echo $transaction['payment_status']; ?></td>
            <td class="left"><?php echo $transaction['pending_reason']; ?></td>
            <td class="left"><?php echo $transaction['created']; ?></td>
            <td class="left">
              <?php if ($transaction['transaction_id']) { ?>
                <a href="<?php echo $transaction['view']; ?>"><?php echo $text_view; ?></a>
                <?php if ($transaction['payment_type'] == 'instant' && ($transaction['payment_status'] == 'Completed' || $transaction['payment_status'] == 'Partially-Refunded')) { ?>
                  &nbsp;<a href="<?php echo $transaction['refund']; ?>"><?php echo $text_refund; ?></a>
                <?php } ?>
              <?php } else { ?>
                <a onclick="resendTransaction(this); return false;" href="<?php echo $transaction['resend']; ?>"><?php echo $text_resend; ?></a>
              <?php } ?>
             </td>
          </tr>
        <?php } ?>
        </tbody>
      </table>
    </td>
  </tr>
</table>

<script type="text/javascript"><!--
function capture() {
  var amt = $('#paypal_capture_amount').val();

  if (amt == '' || amt == 0) {
    alert('<?php echo addslashes($error_capture); ?>');
    return false;
  } else {
    var captureComplete;
    var voidTransaction = false;

    if ($('#paypal_capture_complete').prop('checked') == true) {
      captureComplete = 1;
    } else {
      captureComplete = 0;
    }

    $.ajax({
      url: 'index.php?route=payment/pp_pro_iframe/capture&token=<?php echo $token; ?>',
      type: 'POST',
      data: {
        'amount':amt,
        'order_id':<?php echo $order_id; ?>,
        'complete': captureComplete
      },
      dataType: 'json',
      beforeSend: function() {
        $('#button-capture').hide();
        $('#button-capture').after('<img src="view/image/loading.gif" alt="Loading..." class="loading" id="img_loading_capture" />');
      },
    })
    .fail(function(jqXHR, textStatus, errorThrown) { alert('Status: ' + textStatus + '\r\nError: ' + errorThrown); })
    .done(function(data) {
        if (data.error == false) {
          html = '';

          html += '<tr>';
          html += '<td class="left">' + data.data.transaction_id + '</td>';
          html += '<td class="left">' + data.data.amount + '</td>';
          html += '<td class="left">' + data.data.payment_type + '</td>';
          html += '<td class="left">' + data.data.payment_status + '</td>';
          html += '<td class="left">' + data.data.pending_reason + '</td>';
          html += '<td class="left">' + data.data.created + '</td>';
          html += '<td class="left">';
          html += '<a href="<?php echo $view_link; ?>&transaction_id=' + data.data.transaction_id + '"><?php echo addslashes($text_view); ?></a>';
          html += '&nbsp;<a href="<?php echo $refund_link; ?>&transaction_id=' + data.data.transaction_id + '"><?php echo addslashes($text_refund); ?></a>';
          html += '</td>';
          html += '</tr>';

          $('#paypal_captured').text(data.data.captured);
          $('#paypal_capture_amount').val(data.data.remaining);
          $('#paypal_transactions').append(html);

          if (data.data.void != '') {
            html += '<tr>';
            html += '  <td class="left">' + data.data.void.transaction_id + '</td>';
            html += '  <td class="left">' + data.data.void.amount + '</td>';
            html += '  <td class="left">' + data.data.void.payment_type + '</td>';
            html += '  <td class="left">' + data.data.void.payment_status + '</td>';
            html += '  <td class="left">' + data.data.void.pending_reason + '</td>';
            html += '  <td class="left">' + data.data.void.created + '</td>';
            html += '  <td class="left"></td>';
            html += '</tr>';
          }

          if (data.data.status == 1) {
            $('#capture_status').text('<?php echo addslashes($text_complete); ?>');
            $('.paypal_capture').hide();
          }
        }

        if (data.error == true) {
          alert(data.msg);

          if (data.failed_transaction) {
            html = '';
            html += '<tr>';
            html += '<td class="left"></td>';
            html += '<td class="left">' + data.failed_transaction.amount + '</td>';
            html += '<td class="left"></td>';
            html += '<td class="left"></td>';
            html += '<td class="left"></td>';
            html += '<td class="left">' + data.failed_transaction.created + '</td>';
            html += '<td class="left"><a onclick="resendTransaction(this); return false;" href="<?php echo $resend_link; ?>&paypal_iframe_order_transaction_id=' + data.failed_transaction.paypal_iframe_order_transaction_id + '"><?php echo addslashes($text_resend); ?></a></td>';
            html += '</tr>';

            $('#paypal_transactions').append(html);
          }
        }
    })
    .always(function() {
      $('.loading').remove();
      $('#button-capture').show();
    });
  }
}

function doVoid() {
  if (confirm('<?php echo addslashes($text_confirm_void); ?>')) {
    $.ajax({
      type: 'POST',
      dataType: 'json',
      data: {'order_id':<?php echo $order_id; ?> },
      url: 'index.php?route=payment/pp_pro_iframe/void&token=<?php echo $token; ?>',
      beforeSend: function() {
        $('#button-void').hide();
        $('#button-void').after('<img src="view/image/loading.gif" alt="Loading..." class="loading" id="img_loading_void" />');
      },
    })
    .fail(function(jqXHR, textStatus, errorThrown) { alert('Status: ' + textStatus + '\r\nError: ' + errorThrown); })
    .done(function(data) {
      if (data.error == false) {
        html = '';
        html += '<tr>';
        html += '<td class="left"></td>';
        html += '<td class="left"></td>';
        html += '<td class="left"></td>';
        html += '<td class="left">' + data.data.payment_status + '</td>';
        html += '<td class="left"></td>';
        html += '<td class="left">' + data.data.created + '</td>';
        html += '<td class="left"></td>';
        html += '</tr>';

        $('#paypal_transactions').append(html);
        $('#capture_status').text('<?php echo addslashes($text_complete); ?>');
        $('.paypal_capture_live').hide();
      }

      if (data.error == true) {
        alert(data.msg);
      }
    })
    .always(function() {
      $('.loading').remove();
      $('#button-void').show();
    });
  }
}

function reauthorise() {
  $.ajax({
    type: 'POST',
    dataType: 'json',
    data: {'order_id':<?php echo $order_id; ?> },
    url: 'index.php?route=payment/pp_pro_iframe/reauthorise&token=<?php echo $token; ?>',
    beforeSend: function() {
      $('#button-reauthorise').hide();
      $('#button-reauthorise').after('<img src="view/image/loading.gif" alt="Loading..." class="loading" id="img_loading_reauthorise" />');
    },
  })
  .fail(function(jqXHR, textStatus, errorThrown) { alert('Status: ' + textStatus + '\r\nError: ' + errorThrown); })
  .done(function(data) {
    if (data.error == false) {
      html = '';
      html += '<tr>';
      html += '<td class="left">' + data.data.transaction_id + '</td>';
      html += '<td class="left">0.00</td>';
      html += '<td class="left">' + data.data.payment_type + '</td>';
      html += '<td class="left">' + data.data.payment_status + '</td>';
      html += '<td class="left">' + data.data.pending_reason + '</td>';
      html += '<td class="left">' + data.data.created + '</td>';
      html += '<td class="left"></td>';
      html += '</tr>';

      $('#paypal_transactions').append(html);
      alert('<?php echo addslashes($text_reauthorised); ?>');
    }

    if (data.error == true) {
      alert(data.msg);
    }
  })
  .always(function() {
    $('.loading').remove();
    $('#button-reauthorise').show();
  });
}

function resendTransaction(element) {
  $.ajax({
    type: 'GET',
    dataType: 'json',
    url: $(element).attr('href'),
    beforeSend: function() {
      $(element).hide();
      $(element).after('<img src="view/image/loading.gif" alt="Loading..." class="loading" />');
    },
  })
  .fail(function(jqXHR, textStatus, errorThrown) { alert('Status: ' + textStatus + '\r\nError: ' + errorThrown); })
  .done(function(data) {
    if (data.error) {
      alert(data.error);
    }

    if (data.success) {
      location.reload();
    }
  })
  .always(function() {
    $('.loading').remove();
    $(element).show();
  });
}
//--></script>