<?php echo $header; ?>
<h1>4<span style="font-size:16px;">/4</span> - <?php echo $heading_step_4; ?></h1>
<div id="column-right">
  <ul>
    <li><?php echo $text_license; ?></li>
    <li><?php echo $text_installation; ?></li>
    <li><?php echo $text_configuration; ?></li>
    <li><b><?php echo $text_finished; ?></b></li>
  </ul>
</div>
<div id="content">
  <div class="success"><b><?php echo $text_congratulation; ?></b></div>
  <div class="attention"><?php echo $text_forget; ?></div>
  <div class="finalize">
    <div><a href="../"><img src="view/image/screenshot_1.png" alt="" /></a><br />
      <a href="../" class="button"><?php echo $text_shop; ?></a>
    </div>
    <div><a href="../admin/"><img src="view/image/screenshot_2.png" alt="" /></a><br />
      <a href="../admin/" class="button"><?php echo $text_login; ?></a>
    </div>
  </div>
</div>
<?php echo $footer; ?>