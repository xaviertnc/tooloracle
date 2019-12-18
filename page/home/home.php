<?php //page/home/home.php


if ( ! defined('__APP_START__')) die(); // Silence is golden


DB::connect($app->env->dbConnection);


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


include $app->env->rootPath . '/header.php';

?>
<div class="home-page content">

  <h2>Welcome to TOOL ORACLE</h2>
  <?php if ($message) echo "<b>$message</b>"; ?>

  <p>
    Tool Oracle is here to help you choose the <b>most affordable combination of
    online marketing and productivity tools</b> that check all your boxes.
  </p>
  <p>
    We also provide helpful articles, how-to guides, Q&amp;A's and interactive support on all of
    the tools and services listed.
  </p>

  <section class="row">
    <div class="col" style="margin-right:1em">
      <h3>Why are you here?</h3>
      <ul class="checklist">
        <li><label><input type="checkbox" name="reason[1]">I'm looking for the best tool or service for me.</label></li>
        <li><label><input type="checkbox" name="reason[2]">I want to compare my current tool or services with similar solutions.</label></li>
        <li><label><input type="checkbox" name="reason[3]">I want your honest, unbiased opinion on this tool. What are the pros and cons?</label></li>
        <li><label><input type="checkbox" name="reason[4]">You guys always have the answers to my newby questions that I just can't find anywhere else!</label></li>
        <li><label><input type="checkbox" name="reason[5]">I'm a freelancer and my client uses a tool or service I haven't heard of before.</label></li>
        <li><label><input type="checkbox" name="reason[6]">I have a huge list of contacts (200,000+). What are my options?</label></li>
        <li><label><input type="checkbox" name="reason[7]">Please help me get my email campaign running ASAP!</label></li>
      </ul>
    </div>
    <div class="col">
      <h3>What are you looking for?</h3>
      <div class="button-cloud">
        <button>Email Marketing</button>
        <button>Webinars</button>
        <button>Funnel Builders</button>
        <button>Scheduling</button>
        <button>E-commerce</button>
        <button>Data Analytics</button>
        <button>Viral Campaigns</button>
        <button>CRMs</button>
        <button>All-in-one</button>
        <button>Integrators</button>
        <button>Landing Pages</button>
        <button>Wordpress</button>
      </div>
    </div>
  </section>

  <section class="list-filter">
    <div class="input-group search">
      <label>Search:&nbsp;</label>
      <span class="input-wrapper">
        <input type="text" name="name_term">
        <button class="add-after" name="search">
          <i class="fa fa-search"></i>
        </button>
      </span>
    </div>
  </section>

  <section class="list">

    <div class="list-item list-header">
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
      <div style="flex:2;"><a href="home/toolview?id=<?=$tool->id?>"><?=$tool->name?></a></div>
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

  <section class="list-footer">
    <span>Showing <?=$pagination->offset + 1?> - <?=min($pagination->totalItems,
      $pagination->offset + $pagination->itemsPerPage)?> of <?=$pagination->totalItems?>
    </span>
    <div class="input-group">
      <label>Show:&nbsp;</label>
      <select name="ipp">
        <option value="7">7</option>
        <option value="15" selected>15</option>
        <option value="30">30</option>
        <option value="50">50</option>
        <option value="100">100</option>
      </select>
    </div>
    <span class="pager">
      <?=$view->paginationLinks($pagination)?>
    </span>
  </section>

</div>
<?php

include $app->env->rootPath . '/footer.php';


$_SESSION[$app->id] = $app->state;