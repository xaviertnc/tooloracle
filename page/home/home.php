<?php //page/home/home.php


if ( ! defined('__APP_START__')) die(); // Silence is golden


// DB::connect($app->env->dbConnection);
$db = new OneFile\Database($app->env->dbConnection);


// PAGE
$page = new stdClass();
$page->title = 'Home';


// ALERT MESSAGE
$message = array_get($app->state, 'message', null);
unset($app->state['message']);


// PAGINATION
$pagination = new stdClass();
$pagination->baseUri = '';
$pagination->itemsPerPage = 15;
$pagination->page = array_get($_GET, 'page', 1);
$pagination->totalItems = $db->query('tools')->count();
$pagination->offset = ($pagination->page - 1) * $pagination->itemsPerPage;
$pagination->pages = ceil($pagination->totalItems / $pagination->itemsPerPage);


// DATA
$tools = $db->query('tools')->indexBy('id')
  ->limit($pagination->itemsPerPage, $pagination->offset)
  ->orderby('name desc')
  ->getAll();

$categories = $db->query('tool_categories')->getAll();

// echo '<pre>Pagination: ' . print_r($pagination, true) . '</pre>';
// echo '<pre>Tools: ' . print_r($tools, true) . '</pre>';
// echo '<pre>Categories: ' . print_r($categories, true) . '</pre>';
// echo '<pre>Page: ' . print_r($page, true) . '</pre>';
// echo '<pre>DB: ' . print_r($db, true) . '</pre>';


// VIEW

include $app->env->rootPath . '/header.php';

?>
<div class="container-fixed content">

  <h2>Welcome to TOOL ORACLE</h2>
  <?php if ($message) echo "<b>$message</b>"; ?>

  <p>
    Tool Oracle is here to help you choose the <b>most affordable online marketing and productivity tools</b> that check all your boxes.
  </p>
  <p>
    We also provide helpful articles, how-to guides, Q&amp;A's and interactive support on all of
    the tools and services listed.
  </p>

  <section id="tools-list">
    <h3>What are you looking for?</h3>
    <header>
      <div class="search-bar">
        <div class="input-group">
          <label>Search:&nbsp;</label>
          <span class="input-wrapper">
            <input type="text" name="name_term">
            <button class="add-after" name="search">
              <i class="fa fa-search"></i>
            </button>
          </span>
        </div>
      </div>
      <div class="filter-bar">
        <ul class="mini-card-cloud">
          <li>
            <div class="mini-card-head"></div>
            <div class="mini-card-body"><a><i></i>Email Marketing</a></div>
          </li>
          <li>
            <div class="mini-card-head"></div>
            <div class="mini-card-body"><a><i></i>Webinars</a></div>
          </li>
          <li>
            <div class="mini-card-head"></div>
            <div class="mini-card-body"><a><i></i>Funnel Builders</a></div>
          </li>
          <li>
            <div class="mini-card-head"></div>
            <div class="mini-card-body"><a><i></i>Scheduling</a></div>
          </li>
          <li>
            <div class="mini-card-head"></div>
            <div class="mini-card-body"><a><i></i>E-commerce</a></div>
          </li>
          <li>
            <div class="mini-card-head"></div>
            <div class="mini-card-body"><a><i></i>Data Analytics</a></div>
          </li>
          <li>
            <div class="mini-card-head"></div>
            <div class="mini-card-body"><a><i></i>Viral Referral Campaigns</a></div>
          </li>
          <li>
            <div class="mini-card-head"></div>
            <div class="mini-card-body"><a><i></i>CRMs</a></div>
          </li>
          <li>
            <div class="mini-card-head"></div>
            <div class="mini-card-body"><a><i></i>All-in-one</a></div>
          </li>
          <li>
            <div class="mini-card-head"></div>
            <div class="mini-card-body"><a><i></i>Integrators</a></div>
          </li>
          <li>
            <div class="mini-card-head"></div>
            <div class="mini-card-body"><a><i></i>Landing Pages</a></div>
          </li>
          <li>
            <div class="mini-card-head"></div>
            <div class="mini-card-body"><a><i></i>Wordpress</a></div>
          </li>
        </ul>
      </div>
    </header>
    <main class="list">
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

    </main> <!-- end:list -->
    <footer>
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
    </footer>
  </section>

</div> <!-- end:content -->
<?php

include $app->env->rootPath . '/footer.php';


$_SESSION[$app->id] = $app->state;