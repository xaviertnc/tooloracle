<?php // Tool Oricle - Front Controller

include $_SERVER['DOCUMENT_ROOT'] . '/bootstrap.php';


if ( ! $app->auth->loggedIn) { header('location:/pages/login'); }


$plan_id = array_get($_GET, 'id', 0);


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
    $data = array_get($_POST, 'plan', []);
    cleanDecimals($data, ['price_min', 'price_max']);
    setBools($data, ['free_trail', 'on_special', 'card_required']);
    DB::update('plans', 'WHERE id=:id', $data, ['id' => $plan_id]);
    $feature_ids = json_decode($_POST['plan_features']);
    foreach($feature_ids?:[] as $feature_id) {
      DB::insertInto('plan_features', [
        'plan_id' => $plan_id,
        'feature_id' => $feature_id
      ]);
    }
    $app->state['message'] = 'Saved Changes - ok';
  }

  elseif(isset($_POST['create-metric']))
  {
    $data = array_get($_POST, 'metric', []);
    $data['plan_id'] = $plan_id;
    DB::insertInto('metrics', $data);
    $metric_id = DB::lastInsertId();
    $app->state['message'] = 'Metric Created - ok';
  }

  $_SESSION[$app->id] = $app->state;

  header('location:' . $request->back);
  exit();
}


if (isset($_GET['ajax']))
{
  switch($_GET['ajax'])
  {
    case 'getFeatures':
      $data = [];
      $data['options'] = array_map(function($feature) {
        return ['value' => $feature->id, 'label' => $feature->name];
      }, DB::select('features'));
      $planFeatureIDs = DB::query('plan_features')->where('plan_id', '=', $plan_id)->get('feature_id');
      $data['value'] = array_map(function($planFeature) {
        return (string)$planFeature->feature_id;
      }, $planFeatureIDs);
      break;
    default:
      $data = ['error' => 'Oops, something went wrong!'];
  }
  header('Content-type: application/json');
  header('Cache-Control: no-cache, must-revalidate');
  header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
  echo json_encode($data);
  exit();
}


$page = new stdClass();
$page->title = 'Edit Plan';


$metricTypes = DB::query('metric_types')->getBy('id');

$planSizes = DB::query('plan_sizes')->getBy('id');
$billingTypes = DB::query('billing_types')->getBy('id');

$plan = DB::first('plans', 'WHERE id=?', [$plan_id]);
$tool = DB::first('tools', 'WHERE id=?', [$plan->tool_id]);
$planMetrics = DB::select('metrics WHERE plan_id=?', [$plan_id]);

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
    <?=$plan->name?> <small><i>(Edit - <?=$plan_id?>)</i></small>
  </h2>

  <!-- BACK BUTTON -->
  <a class="btn pull-right" href="<?=$request->back?>"
    onclick="App.goBack(event, this)" style="position:relative;top:-2px">
    <i class="fa fa-arrow-left"></i> Back
  </a>

  <!-- TAB BAR -->
  <div class="tabbar">
    <ul class="tabs">
      <li class="active" onclick="App.selectTab(this)"><label for="tab-select1">Plan</label></li>
      <li onclick="App.selectTab(this)"><label for="tab-select2">Plan Metrics</label></li>
      <li onclick="App.selectTab(this)"><label for="tab-select3">Features</label></li>
    </ul>
  </div>

  <!-- CREATE METRIC FORM -->
  <form id="create-metric-form" method="POST">
    <input type="checkbox" id="toggle-modal" name="_toggle_modal"
      style="position:absolute;left:-999;opacity:0">
    <section class="modal">
      <div class="modal-inner">
        <label for="toggle-modal" class="btn pull-right">Close X</label>
        <div class="field" style="clear:both">
          <label>Name</label>
          <input type="text" id="metric-name-field" name="metric[name]" required>
        </div>
        <div class="field">
          <label>Type</label>
          <select name="metric[type_id]" required>
            <option value="0">- Select Type -</option>
            <?php foreach($metricTypes as $metricType): ?>
            <option value="<?=$metricType->id?>"><?=$metricType->name?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label>Description</label>
          <textarea name="metric[description]" rows="3"></textarea>
        </div>
        <div class="field currency">
          <label>Range <small>(Min)</small></label>
          <div class="input-group">
            <span class="add-on add-on-left">$</span>
            <input type="text" name="metric[min]" value="">
          </div>
        </div>
        <div class="field currency">
          <label>Range <small>(Max)</small></label>
          <div class="input-group">
            <span class="add-on add-on-left">$</span>
            <input type="text" name="metric[max]" value="">
          </div>
        </div>
        <div class="field">
          <label>Display Order</label>
          <input name="metric[seq]" type="number" min="0" value="0" style="max-width:70px;min-width:auto">
        </div>
        <div class="actionbar">
          <input type="submit" name="create-metric" value="Add Metric">
        </div>
      </div>
    </section>
  </form>

  <!-- EDIT PLAN FORM -->
  <form id="edit-plan-form" method="POST">
    <!-- PLAN TAB -->
    <input id="tab-select1" class="tab-select" type="radio" name="_tabs" checked>
    <div class="tab-content">
      <div class="row">
        <section class="col">
          <div class="field">
            <label>Name</label>
            <input type="text" id="name-field" name="plan[name]" value="<?=$plan->name?>" required>
          </div>
          <div class="field">
            <label>Description</label>
            <textarea name="plan[description]" rows="10"
              style="width:96%;max-width:400px"><?=$plan->description?></textarea>
          </div>
          <div class="field number">
            <label>Display Order</label>
            <input name="plan[seq]" type="number" min="0" value="<?=$plan->seq?>" style="max-width:70px">
          </div>
        </section>
        <section class="col">
          <div class="field">
            <label>Plan Size</label>
            <select name="plan[size_id]" onchange="App.fetchSubCategories(this.value)" required>
              <option value="0">- Select Size -</option>
              <?php foreach($planSizes as $planSize): ?>
              <option value="<?=$planSize->id?>"<?=$planSize->id==$plan->size_id ? ' selected' : ''?>><?=$planSize->name?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label>Billing Type</label>
            <select id="subcategories" name="plan[billing_type_id]">
              <option value="0">- Select Billing Type -</option>
              <?php foreach($billingTypes as $billingType): ?>
              <option value="<?=$billingType->id?>"<?=$billingType->id==$plan->billing_type_id ? ' selected' : ''?>><?=$billingType->name?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field currency">
            <label>Price <small>(Min)</small></label>
            <div class="input-group">
              <span class="add-on add-on-left">$</span>
              <input type="text" name="plan[price_min]"
                value="<?=Format::decimal($plan->price_min, null, 2)?>">
            </div>
          </div>
          <div class="field currency">
            <label>Price <small>(Max)</small></label>
            <div class="input-group">
              <span class="add-on add-on-left">$</span>
              <input type="text" name="plan[price_max]"
                value="<?=Format::decimal($plan->price_max, null, 2)?>">
            </div>
          </div>
        </section>
      </div>
    </div>
    <!-- METRICS TAB -->
    <input id="tab-select2" class="tab-select" type="radio" name="_tabs">
    <div class="tab-content">
      <div class="list-header">
        <h3>Plan Metrics</h3>
        <button onclick="setTimeout(()=>document.getElementById('metric-name-field').focus())"
          type="button" style="margin-left:auto">
          <label for="toggle-modal">+ Add Metric</label>
        </button>
      </div>
      <div class="metrics">
        <?php foreach($planMetrics as $metric): ?>
        <div class="metric list-item">
          <span style="flex:1;min-width:80px"><?=$metric->name?></span>
          <span style="flex:2;min-width:130px"><?=$metric->description?></span>
          <span style="flex:2;min-width:130px;padding:0.3em 0">
            <?=Format::decimal($metric->min, 0, 0)?>
            <?=$metric->max > 0 ? ' - '.Format::decimal($metric->max, 0, 0) : ''?>
          </span>
          <span style="margin-left:auto">
            <a class="btn btn-link" href="/pages/metric/edit.php?id=<?=$metric->id?>">
              <i class="fa fa-pencil" aria-hidden="true"></i>
            </a>
          </span>
        </div>
        <?php endforeach; ?>
      </div>
      <br>
    </div>
    <!-- FEATURES TAB -->
    <input id="tab-select3" class="tab-select" type="radio" name="_tabs">
    <div class="tab-content">
      <div class="row">
        <section class="col">
          <div class="field" style="max-width:300px">
            <label>Features</label>
            <span id="plan-features-select"></span>
            <input id="features" type="hidden" name="plan_features" value="">
            <small><i>Click on field to edit</i></small>
          </div>
        </section>
        <section class="col">
          <div class="field">
            <label class="checkbox">
              <input type="checkbox" name="plan[free_trail]"<?=$plan->free_trail?' checked':''?>>
              <span>Free Trail</span>
            </label>
          </div>
          <div class="field">
            <label class="checkbox">
              <input type="checkbox" name="plan[card_required]"<?=$plan->card_required?' checked':''?>>
              <span>Credit Card Required <small>(Pre Trail)</small></span>
            </label>
          </div>
          <div class="field">
            <label class="checkbox">
              <input type="checkbox" name="plan[on_special]"<?=$plan->on_special?' checked':''?>>
              <span>Special Offer(s)</span>
            </label>
          </div>
        </section>
      </div>
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

    App.fetch('/pages/plan/edit.php?ajax=getFeatures&id=<?=$plan_id?>', function(resp) {
      console.log('Ajax Resp:', resp);
      App.planFeaturesSelect = new SelectPure('#plan-features-select', {
        options: resp.options,
        value: resp.value,
        multiple: true,
        icon: "fa fa-times",
        onChange: function(value) {
          console.log(value);
          let elSelect = App.planFeaturesSelect._parent.get();
          elSelect.nextElementSibling.value = JSON.stringify(value);
        },
      });
    });

  </script>

</div>
<?php

include $app->rootPath . '/footer.php';


$_SESSION[$app->id] = $app->state;