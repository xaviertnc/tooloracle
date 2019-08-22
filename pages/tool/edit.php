<?php // Tool Oricle - Front Controller

include $_SERVER['DOCUMENT_ROOT'] . '/bootstrap.php';


if ( ! $app->auth->loggedIn) { header('location:/pages/login'); }


$tool_id = array_get($_GET, 'id', 0);


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
  if (isset($_POST['update-tool']))
  {
    $data = array_get($_POST, 'tool', []);
    cleanDecimals($data, ['start_price']);
    setBools($data, ['free_version', 'free_trail', 'special_offer', 'card_required']);
    DB::update('tools', 'WHERE id=:id', $data, ['id' => $tool_id]);
    $feature_ids = json_decode($_POST['tool_features']);
    foreach($feature_ids?:[] as $feature_id) {
      DB::insertInto('tool_features', [
        'tool_id' => $tool_id,
        'feature_id' => $feature_id
      ]);
    }
    $app->state['message'] = 'Saved Changes - ok';
  }

  elseif(isset($_POST['create-plan']))
  {
    $data = array_get($_POST, 'plan', []);
    cleanDecimals($data, ['price_min', 'price_max']);
    setBools($data, ['free_trail', 'on_special', 'card_required']);
    $data['tool_id'] = $tool_id;
    DB::insertInto('plans', $data);
    $plan_id = DB::lastInsertId();
    $feature_ids = json_decode($_POST['plan_features']);
    foreach($feature_ids?:[] as $feature_id) {
      DB::insertInto('plan_features', [
        'plan_id' => $plan_id,
        'feature_id' => $feature_id
      ]);
    }
    $app->state['message'] = 'Plan Created - ok';
  }

  $_SESSION[$app->id] = $app->state;

  header('location:' . $request->back);
  exit();
}


if (isset($_GET['ajax']))
{
  switch($_GET['ajax'])
  {
    case 'getSubCategories':
      $data = DB::select('tool_subcategories WHERE category_id=?',
        [array_get($_GET, 'id', 0)]);
      break;
    case 'getFeatures':
      $data = [];
      $data['options'] = array_map(function($feature) {
        return ['value' => $feature->id, 'label' => $feature->name];
      }, DB::select('features'));
      $toolFeatureIDs = DB::query('tool_features')->where('tool_id', '=', $tool_id)->get('feature_id');
      $data['value'] = array_map(function($toolFeature) {
        return (string)$toolFeature->feature_id;
      }, $toolFeatureIDs);
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
$page->title = 'Edit Tool';


$planSizes = DB::query('plan_sizes')->getBy('id');
$billingTypes = DB::query('billing_types')->getBy('id');

$tool = DB::first('tools', 'WHERE id=?', [$tool_id]);
$toolPlans = DB::select('plans WHERE tool_id=?', [$tool_id]);

$categories = DB::select('tool_categories');
$subCategories = DB::select('tool_subcategories WHERE category_id=?', [$tool->category_id]);


$message = array_get($app->state, 'message', null);
unset($app->state['message']);


include $app->rootPath . '/header.php';

?>
<div class="page tool-edit">
  <?php if ($message):?>
  <h1 class="green"><?=$message?></h1>
  <?php endif; ?>

  <!-- PAGE TITLE -->
  <h2><?=$tool->name?> <small><i>(Edit - <?=$tool_id?>)</i></small></h2>

  <!-- BACK BUTTON -->
  <a class="btn pull-right" href="<?=$request->back?>"
    onclick="App.goBack(event, this)" style="position:relative;top:-2px">
    <i class="fa fa-arrow-left"></i> Back
  </a>

  <!-- TAB BAR -->
  <div class="tabbar">
    <ul class="tabs">
      <li class="active" onclick="App.selectTab(this)"><label for="tab-select1">Tool</label></li>
      <li onclick="App.selectTab(this)"><label for="tab-select2">Pricing Plans</label></li>
      <li onclick="App.selectTab(this)"><label for="tab-select3">Features</label></li>
      <li onclick="App.selectTab(this)"><label for="tab-select4">Reviews</label></li>
    </ul>
  </div>

  <!-- CREATE PLAN FORM -->
  <form id="create-plan-form" method="POST">
    <input type="checkbox" id="toggle-modal" name="_toggle_modal"
      style="position:absolute;left:-999;opacity:0">
    <section class="modal">
      <div class="modal-inner">
        <label for="toggle-modal" class="btn pull-right">Close X</label>
        <div class="field" style="clear:both">
          <label>Name</label>
          <input type="text" id="plan-name-field" name="plan[name]" required>
        </div>
        <div class="field">
          <label>Size</label>
          <select name="plan[size_id]" required>
            <option value="0">- Select Size -</option>
            <?php foreach($planSizes as $planSize): ?>
            <option value="<?=$planSize->id?>"><?=$planSize->name?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label>Billing Type</label>
          <select name="plan[billing_type_id]" required>
            <option value="0">- Select Billing Type -</option>
            <?php foreach($billingTypes as $billingType): ?>
            <option value="<?=$billingType->id?>"><?=$billingType->name?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label>Description</label>
          <textarea name="plan[description]" rows="3"></textarea>
        </div>
        <div class="row">
          <section class="col">
            <div class="field">
              <label class="checkbox">
                <input type="checkbox" name="plan[popular]">
                <span>Popular / Recommended</span>
              </label>
            </div>
            <div class="field">
              <label class="checkbox">
                <input type="checkbox" name="plan[free_trail]">
                <span>Free Trail</span>
              </label>
            </div>
            <div class="field">
              <label class="checkbox">
                <input type="checkbox" name="plan[card_required]">
                <span>Credit Card Required <small>(Pre Trail)</small></span>
              </label>
            </div>
            <div class="field">
              <label class="checkbox">
                <input type="checkbox" name="plan[on_special]">
                <span>On Special</span>
              </label>
            </div>
          </section>
          <section class="col">
            <div class="field currency">
              <label>Price <small>(Min)</small></label>
              <div class="input-group">
                <span class="add-on add-on-left">$</span>
                <input type="text" name="plan[price_min]" value="">
              </div>
            </div>
            <div class="field currency">
              <label>Price <small>(Max)</small></label>
              <div class="input-group">
                <span class="add-on add-on-left">$</span>
                <input type="text" name="plan[price_max]" value="">
              </div>
            </div>
          </section>
        </div>
        <div class="field" style="max-width:550px">
          <label>Features</label>
          <span id="plan-features-select"></span>
          <input id="plan-features" type="hidden" name="plan_features" value="">
        </div>
        <div class="field">
          <label>Price Formula</label>
          <textarea name="plan[price_formula]" rows="3"></textarea>
        </div>
        <div class="field">
          <label>Display Order</label>
          <input name="plan[seq]" type="number" min="0" value="0" style="max-width:70px;min-width:auto">
        </div>
        <div class="actionbar">
          <input type="submit" name="create-plan" value="Add Plan">
        </div>
      </div>
    </section>
  </form>

  <!-- EDIT TOOL FORM -->
  <form id="edit-tool-form" method="POST">
    <!-- TOOL TAB -->
    <input id="tab-select1" class="tab-select" type="radio" name="_tabs" checked>
    <div class="tab-content">
      <div class="row">
        <section class="col">
          <div class="field">
            <label>Name</label>
            <input type="text" id="name-field" name="tool[name]" value="<?=$tool->name?>" required>
          </div>
          <div class="field">
            <label>Description</label>
            <textarea name="tool[description]" rows="9"
              style="width:96%;max-width:445px"><?=$tool->description?></textarea>
          </div>
          <div class="field">
            <label>Website</label>
            <input type="url" name="tool[website]" value="<?=$tool->website?>" required>
          </div>
          <div class="field number">
            <label>Display Order</label>
            <input name="tool[seq]" type="number" min="0" value="<?=$tool->seq?>" style="max-width:70px">
          </div>
        </section>
        <section class="col">
          <div class="field">
            <label>Category</label>
            <select name="tool[category_id]" onchange="App.fetchSubCategories(this.value)" required>
              <option value="0">- Select Category -</option>
              <?php foreach($categories as $category): ?>
              <option value="<?=$category->id?>"<?=$category->id==$tool->category_id ? ' selected' : ''?>><?=$category->name?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label>Sub Category</label>
            <select id="subcategories" name="tool[subcategory_id]">
              <option value="0">- Select Sub Category -</option>
              <?php foreach($subCategories as $subCategory): ?>
              <option value="<?=$subCategory->id?>"<?=$subCategory->id==$tool->subcategory_id ? ' selected' : ''?>><?=$subCategory->name?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field currency">
            <label>Price <small>(Start)</small></label>
            <div class="input-group">
              <span class="add-on add-on-left">$</span>
              <input type="text" name="tool[start_price]" value="<?=Format::decimal($tool->start_price, null, 2)?>">
            </div>
          </div>
          <div class="field toggle">
            <label class="checkbox">
              <input type="checkbox" name="tool[free_version]"<?=$tool->free_version?' checked':''?>>
              <span>Free Version</span>
            </label>
          </div>
          <div class="field toggle">
            <label class="checkbox">
              <input type="checkbox" name="tool[free_trail]"<?=$tool->free_trail?' checked':''?>>
              <span>Free Trail</span>
            </label>
          </div>
          <div class="field">
            <label class="checkbox">
              <input type="checkbox" name="tool[card_required]"<?=$tool->card_required?' checked':''?>>
              <span>Credit Card Required <small>(Pre Trail)</small></span>
            </label>
          </div>
          <div class="field toggle">
            <label class="checkbox">
              <input type="checkbox" name="tool[special_offer]"<?=$tool->special_offer?' checked':''?>>
              <span>Special Offer(s)</span>
            </label>
          </div>
        </section>
      </div>
    </div>
    <!-- PRICING TAB -->
    <input id="tab-select2" class="tab-select" type="radio" name="_tabs">
    <div class="tab-content">
      <div class="list-header">
        <h3>Pricing Plans</h3>
        <button onclick="setTimeout(()=>document.getElementById('plan-name-field').focus())"
          type="button" style="margin-left:auto">
          <label for="toggle-modal">+ Add Plan</label>
        </button>
      </div>
      <div class="plans">
        <?php $yes = '<i class="fa fa-check green"></i>&nbsp;'; ?>
        <?php $no = '<i class="fa fa-times red"></i>&nbsp;'; ?>
        <?php foreach($toolPlans as $plan): ?>
        <?php $planSize = array_get($planSizes, $plan->size_id); ?>
        <?php $billingType = array_get($billingTypes, $plan->billing_type_id); ?>
        <div class="plan list-item">
          <span style="flex:1;min-width:65px"><?=$plan->name?></span>
          <span style="flex:2;min-width:130px;padding:0.3em 0">
            <?php if($plan->size_id == 1):?>
            $0
            <?php else:?>
            $<?=Format::decimal($plan->price_min, '', 2)?>
            <?=$plan->price_max > 0 ? ' - $' . Format::decimal($plan->price_max, '', 2) : ''?>
            <small><i>/ <?=$billingType->name?></i></small>
            <?php endif; ?>
          </span>
          <span style="flex:2;min-width:130px"><?=nl2br($plan->description)?></span>
          <span style="flex:2;min-width:130px">
            <?php if($plan->size_id == 1):?>
            -
            <?php else:?>
            <?=$plan->free_trail ? $yes : $no?>Free Trail<br>
            <?=$plan->card_required ? $yes : $no?>Card Required
            <?php endif; ?>
          </span>
          <span style="margin-left:auto">
            <a class="btn btn-link" href="/pages/plan/edit.php?id=<?=$plan->id?>">
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
            <span id="tool-features-select"></span>
            <input id="features" type="hidden" name="tool_features" value="">
            <small><i>Click on field to edit</i></small>
          </div>
        </section>
        <section class="col">
          <div class="field" style="max-width:300px">
            <label>Features Overview</label>
            <textarea name="tool[features_overview]" rows="9"><?=$tool->features_overview?></textarea>
          </div>
        </section>
      </div>
    </div>
    <!-- REVIEWS TAB -->
    <input id="tab-select4" class="tab-select" type="radio" name="_tabs">
    <div class="tab-content">
      <div class="row">
        <section class="col">
          <div class="field">
            <label>Oracle Review</label>
            <textarea name="tool[oracle_review]" rows="9"><?=$tool->oracle_review?></textarea>
          </div>
        </section>
        <section class="col">
          <div class="field number">
            <label>Oracle Rating <small>(1 - 10)</small></label>
            <input type="number" name="tool[oracle_rating]" value="<?=$tool->oracle_rating?>" min="0">
          </div>
        </section>
      </div>
    </div>
    <!-- ACTION BAR -->
    <div class="actionbar">
      <input type="submit" name="update-tool" value="Save Changes">
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
      },

      fetchSubCategories: function(category_id) {
        console.log('Hi, fetching sub-categories!');
        this.fetch('/pages/tool/edit.php?ajax=getSubCategories&id=' + category_id, function(optionsData) {
          var options = [];
          var elSelect = document.getElementById('subcategories');
          var elNullOption = elSelect.firstElementChild;
          elSelect.innerHTML = null;
          elSelect.appendChild(elNullOption);
          optionsData.forEach(function(optionData) {
            options.push(new App.Option(optionData.id, optionData.name));
          });
          options.forEach(function(option) {
            elSelect.appendChild(option.el);
          });
        });
      }
    };

    App.fetch('/pages/tool/edit.php?ajax=getFeatures&id=<?=$tool_id?>', function(resp) {
      console.log('Ajax Resp:', resp);
      App.toolFeaturesSelect = new SelectPure('#tool-features-select', {
        options: resp.options,
        value: resp.value,
        multiple: true,
        icon: "fa fa-times",
        onChange: function(value) {
          console.log(value);
          let elSelect = App.toolFeaturesSelect._parent.get();
          elSelect.nextElementSibling.value = JSON.stringify(value);
        },
      });
      App.planFeaturesSelect = new SelectPure('#plan-features-select', {
        options: resp.options,
        value: [],
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