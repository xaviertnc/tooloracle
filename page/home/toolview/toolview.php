<?php //pages/home/toolview.php


if ( ! defined('__APP_START__')) die(); // Silence is golden


$db = new OneFile\Database($app->env->dbConnection);


$page = new stdClass();
$page->title = 'Tool Detail';


$tool_id = array_get($_GET, 'id', 0);


$tool = $db->query('tools')->where('id=?', $tool_id)->getFirst();
$plans = $db->query('plans')->where('tool_id=?', $tool_id)->getAll();

foreach($plans as $plan)
{
  $plan->metrics = $db->query('plan_metrics')->where('plan_id=?', $plan->id)->getAll();
}


include $app->env->rootPath . '/header.php';

?>
<div class="page tool-view container-fixed">
  <style>
    p { margin: 0.5em; line-height: 1.15em; }
    h3 { margin: 2em 0 0; }
    .stars::after { padding-left: 0.1em; }
  </style>


  <h2><?=$tool->name?></h2>


  <h3>Description</h3>
  <p><?=nl2br($tool->description)?></p>


  <h3>Features Overview</h3>
  <p><?=nl2br($tool->features_overview?:'None')?></p>


  <h3>Oracle Review</h3>
  <div class="stars" style="--rating: <?=$tool->oracle_rating/2?>;"
    aria-label="Rating of this product is <?=$tool->oracle_rating/2?> out of 5.">
   <p><?=nl2br($tool->oracle_review)?></p>
  </div>


  <h3>Plans</h3>
  <?php foreach($plans as $plan): ?>
  <div class="list-item">
    <span style="flex:1"><?=$plan->name?></span>
    <span style="flex:1">$<?=Format::decimal($plan->price_min)?></span>
    <span style="flex:1">$<?=Format::decimal($plan->price_max)?></span>
    <span style="flex:1">
      <table>
      <?php foreach($plan->metrics as $metric): ?>
      <tr>
        <td style="min-width:80px"><?=$metric->name?></td>
        <td><?=Format::decimal($metric->min)?></td>
        <td> - </td>
        <td><?=Format::decimal($metric->max)?></td>
      </tr>
      <?php endforeach; ?>
      </table>
    </span>
  </div>
  <?php endforeach; ?>

  <br>
  <br>

  <a class="btn" href="<?=$request->back?>"
    onclick="App.goBack(event, this)" style="position:relative;top:-2px;margin-left:auto">
    <i class="fa fa-arrow-left"></i> Back
  </a>

</div>
<?php

include $app->env->rootPath . '/footer.php';


$_SESSION[$app->id] = $app->state;