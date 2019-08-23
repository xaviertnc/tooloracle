<?php //page/home/home.php


if ( ! defined('__APP_START__')) die(); // Silence is golden


DB::connect($app->dbConnection);


// PAGE
$page = new stdClass();
$page->title = 'Home';


// ALERT MESSAGE
$message = array_get($app->state, 'message', null);
unset($app->state['message']);


// PAGINATION
$pagination = new stdClass();
$pagination->itemsPerPage = 15;
$pagination->baseUri = '';
$pagination->page = array_get($_GET, 'page', 1);
$pagination->totalItems = DB::query('tools')->count();
$pagination->offset = ($pagination->page - 1) * $pagination->itemsPerPage;
$pagination->pages = ceil($pagination->totalItems / $pagination->itemsPerPage);


// DATA
$tools = DB::query('tools')->limit($pagination->offset, $pagination->itemsPerPage)->get();
$categories = DB::select('tool_categories');


include $app->rootPath . '/header.php';

?>
<div class="home-page">

  <h2>Welcome to TOOL ORACLE</h2>
  <p>
    Tool Oracle is here to help you choose the <b>most affordable and efficient
    combination of online tools</b> that check all your boxes.
  </p>
  <p>
    We don't just suggest perfect combinations, but we also provide helpful
    articles, how-to guides, Q&A's and interactive support on most of the tools
    and services listed here.
  </p>

  <?php if ($message):?>
  <h1><?=$message?></h1>
  <?php endif; ?>

  <section>
    <div class="list-header">
      <span style="margin-left:auto;display:flex;align-items:center">
        <?=$view->paginationLinks($pagination)?>
      </span>
    </div>
    <div class="list-item">
      <div style="flex:0.5;"><b>#</b></div>
      <div style="flex:2;"><b>Tool Name</b></div>
      <div style="flex:1;text-align:center"><b>Free Version</b></div>
      <div style="flex:1;text-align:center"><b>Free Trail</b></div>
      <div style="flex:1;text-align:center"><b>CCard Req.</b></div>
      <div style="flex:2;text-align:center"><b>Start Price <small>(mth)</small></b></div>
      <div style="flex:1;text-align:center"><b>Rating</b></div>
    </div>
    <?php $yes = '<i class="fa fa-check green"></i>&nbsp;'; ?>
    <?php $no = '<i class="fa fa-times red"></i>&nbsp;'; ?>
    <?php foreach($tools as $index => $tool): ?>

    <div class="list-item">
      <div style="flex:0.5;"><?=($pagination->offset + $index + 1) . '. '?></div>
      <div style="flex:2;"><a href="tool?id=<?=$tool->id?>"><?=$tool->name?></a></div>
      <div style="flex:1;text-align:center"><?=$tool->free_version?$yes:$no?></div>
      <div style="flex:1;text-align:center"><?=$tool->free_trail?$yes:$no?></div>
      <div style="flex:1;text-align:center"><?=$tool->card_required?$yes:$no?></div>
      <div style="flex:2;text-align:center"><?=$tool->start_price?'$'.$tool->start_price:''?></div>
      <div style="flex:1;text-align:center;--rating:<?=$tool->oracle_rating/2?>;" class="stars"
          aria-label="Rating of this product is <?=$tool->oracle_rating/2?> out of 5.">
      </div>
    </div>
    <?php endforeach; ?>
    <?php if( ! $tools):?>

    <div class="list-item">Nothing to display...</div>
    <?php endif; ?>
  </section>

  <section class="list-footer actionbar">
    <small>
      Showing <?=$pagination->offset + 1?> -
      <?=min($pagination->totalItems, $pagination->offset + $pagination->itemsPerPage)?> of
      <?=$pagination->totalItems?>
    </small>
    <span style="margin-left:auto;display:flex;align-items:center">
      <?=$view->paginationLinks($pagination)?>
    </span>
  </section>

</div>
<?php

include $app->rootPath . '/footer.php';


$_SESSION[$app->id] = $app->state;