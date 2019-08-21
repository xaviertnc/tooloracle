<?php // Tool Oricle - Front Controller

include $_SERVER['DOCUMENT_ROOT'] . '/bootstrap.php';


if ( ! $app->auth->loggedIn) { header('location:/pages/login'); }


$metric_id = array_get($_GET, 'id', 0);


DB::connect($app->dbConnection);


function cleanDecimals(&$data, array $keys) {
  foreach($keys as $key) {
    $data[$key] = str_replace(',', '', $data[$key]);
  }
}

function setBools(&$data, array $keys) {
  foreach($keys as $key) {
    $data[$key] = isset($data[$key]) ? 1 : 0;
  }
}


if ($request->method == 'POST')
{
  if (isset($_POST['update-plan']))
  {
    $data = array_get($_POST, 'metric', []);
    cleanDecimals($data, ['min', 'max']);
    DB::update('metrics', 'WHERE id=:id', $data, ['id' => $metric_id]);
    $app->state['message'] = 'Saved Changes - ok';
  }

  $_SESSION[$app->id] = $app->state;

  header('location:' . $request->back);
  exit();
}


if (isset($_GET['ajax']))
{
  switch($_GET['ajax'])
  {
    default:
      $data = ['error' => 'Oops, something went wrong!'];
  }
  header('Content-type: application/json');
  header('Cache-Control: no-cache, must-revalidate');
  header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
  echo json_encode($data);
  exit();
}


$metricTypes = DB::query('metric_types')->getBy('id');

$metric = DB::first('metrics', 'WHERE id=?', [$metric_id]);
$plan = DB::first('plans', 'WHERE id=?', [$metric->plan_id]);
$tool = DB::first('tools', 'WHERE id=?', [$plan->tool_id]);

$message = array_get($app->state, 'message', null);
unset($app->state['message']);


include $app->rootPath . '/header.php';

?>
<div class="page plan-edit">
  <?php if ($message):?>
  <h1 class="green"><?=$message?></h1>
  <?php endif; ?>

  <!-- PAGE TITLE -->
  <h2>
    <?=$tool->name?><br>
    <?=$plan->name?><br>
    <?=$metric->name?> <small><i>(Edit - <?=$metric_id?>)</i></small>
  </h2>

  <!-- BACK BUTTON -->
  <a class="btn pull-right" href="<?=$request->back?>"
    onclick="App.goBack(event, this)" style="position:relative;top:-2px">
    <i class="fa fa-arrow-left"></i> Back
  </a>

  <!-- TAB BAR -->
  <div class="tabbar">
    <ul class="tabs">
      <li class="active" onclick="App.selectTab(this)"><label for="tab-select1">Metric</label></li>
      <li onclick="App.selectTab(this)"><label for="tab-select2">More</label></li>
    </ul>
  </div>

  <!-- EDIT METRIC FORM -->
  <form id="edit-metric-form" method="POST">
    <!-- METRIC TAB -->
    <input id="tab-select1" class="tab-select" type="radio" name="_tabs" checked>
    <div class="tab-content">
      <div class="row">
        <section class="col">
          <div class="field">
            <label>Name</label>
            <input type="text" id="name-field" name="metric[name]" value="<?=$metric->name?>" required>
          </div>
          <div class="field">
            <label>Description</label>
            <textarea name="metric[description]" rows="10"
              style="width:96%;max-width:400px"><?=$metric->description?></textarea>
          </div>
          <div class="field number">
            <label>Display Order</label>
            <input name="metric[seq]" type="number" min="0" value="<?=$metric->seq?>" style="max-width:70px">
          </div>
        </section>
        <section class="col">
          <div class="field">
            <label>Type</label>
            <select name="metric[type_id]" onchange="App.fetchSubCategories(this.value)" required>
              <option value="0">- Select Type -</option>
              <?php foreach($metricTypes as $metricType): ?>
              <option value="<?=$metricType->id?>"<?=$metricType->id==$metric->type_id ? ' selected' : ''?>><?=$metricType->name?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field currency">
            <label>Range <small>(Min)</small></label>
            <div class="input-group">
              <span class="add-on add-on-left"></span>
              <input type="text" name="metric[min]"
                value="<?=Format::decimal($metric->min, null, 2)?>">
            </div>
          </div>
          <div class="field currency">
            <label>Range <small>(Max)</small></label>
            <div class="input-group">
              <span class="add-on add-on-left"></span>
              <input type="text" name="metric[max]"
                value="<?=Format::decimal($metric->max, null, 2)?>">
            </div>
          </div>
        </section>
      </div>
    </div>
    <!-- MORE TAB -->
    <input id="tab-select2" class="tab-select" type="radio" name="_tabs">
    <div class="tab-content">
      <h3>More...</h3>
      <br>
    </div>
    <!-- ACTION BAR -->
    <div class="actionbar">
      <input type="submit" name="update-plan" value="Save Changes">
    </div>
    <br>
  </form>

  <script src="/js/pure-select.min.js"></script>

  <script>
    window.App = {

      Option: function (value, label) {
        this.el = document.createElement('option');
        this.el.value = value;
        this.el.innerHTML = label;
      },

      goto: function(url) {
        window.location.href = url;
      },

      goBack: function(event, elLink) {
        if (elLink.href === window.location.href.toString()) {
          event.preventDefault();
          history.go(-1);
        }
      },

      fetch: function(url, onSuccess) {
        var oReq = new XMLHttpRequest();
        oReq.onload = function reqListener() {
          var data = JSON.parse(this.responseText);
          console.log(data);
          onSuccess(data);
        }
        oReq.onerror = function reqError(err) {
          console.log('Fetch Error :-S', err);
        };
        oReq.open('get', url, true);
        oReq.send();
      },

      selectTab: function(elSelectedTab) {
        var i, elTab;
        var tabs = elSelectedTab.parentElement.children;
        for (i in tabs) { elTab = tabs[i];
          if (elTab !== elSelectedTab) { elTab.className = ''; }
        }
        elSelectedTab.className = 'active';
      },

      toggleDetail: function(elCheckbox) {
        elCheckbox.parentElement.parentElement.nextElementSibling.classList.toggle('hidden');
      }
    };
  </script>

</div>
<?php

include $app->rootPath . '/footer.php';


$_SESSION[$app->id] = $app->state;