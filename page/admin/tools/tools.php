<?php //page/admin/tools/tools.php


if ( ! defined('__APP_START__')) die(); // Silence is golden


if ( ! $auth->loggedIn) { header('location:login'); }


DB::connect($app->env->dbConnection);


if ($request->method == 'POST') {
  $data = [
    'name' => $_POST['name'],
    'website' => $_POST['website'],
    'category_id' => $_POST['category_id'],
    'subcategory_id' => $_POST['subcategory_id'],
    'description' => $_POST['description'],
    'seq' => $_POST['seq'],
  ];
  DB::insertInto('tools', $data);

  $tool_id = DB::lastInsertId();

  $feature_ids = json_decode($_POST['features']);

  foreach($feature_ids?:[] as $feature_id) {
    DB::insertInto('tool_features', [
      'tool_id' => $tool_id,
      'feature_id' => $feature_id
    ]);
  }

  $app->state['message'] = 'Added: ' . $_POST['name'] . ' - ok';
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
      $data = array_map(function($feature) {
        return ['value' => $feature->id, 'label' => $feature->name];
      }, DB::select('features'));
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


// PAGE
$page = new stdClass();
$page->title = 'Tools Admin';


// ALERT MESSAGE
$message = array_get($app->state, 'message', null);
unset($app->state['message']);


// PAGINATION
$pagination = new stdClass();
$pagination->itemsPerPage = 10;
$pagination->baseUri = 'admin/tools';
$pagination->page = array_get($_GET, 'page', 1);
$pagination->totalItems = DB::query('tools')->count();
$pagination->offset = ($pagination->page - 1) * $pagination->itemsPerPage;
$pagination->pages = ceil($pagination->totalItems / $pagination->itemsPerPage);


// DATA
$tools = DB::query('tools')->limit($pagination->offset, $pagination->itemsPerPage)->get();
$categories = DB::select('tool_categories');


include $app->env->rootPath . '/header.php';

?>
<div class="page home">

  <h2>ADMIN Tools</h2>

  <?php if ($message):?>
  <h1><?=$message?></h1>
  <?php endif; ?>

  <form method="POST">
    <input type="checkbox" id="toggle-modal" name="_toggle_modal"
      style="position:absolute;left:-999;opacity:0">
    <section class="modal">
      <div class="modal-inner">
        <label for="toggle-modal" class="btn pull-right">Close X</label>
        <div class="field">
          <label>Name</label>
          <input type="text" id="name-field" name="name" required>
        </div>
        <div class="field">
          <label>Category</label>
          <select name="category_id" onchange="App.fetchSubCategories(this.value)" required>
            <option value="0">- Select Category -</option>
            <?php foreach($categories as $category): ?>
            <option value="<?=$category->id?>"><?=$category->name?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label>Sub Category</label>
          <select id="subcategories" name="subcategory_id">
            <option value="0">- Select Sub Category -</option>
          </select>
        </div>
        <div class="field">
          <label>Website</label>
          <input type="url" name="website" required>
        </div>
        <div class="field">
          <label>Description</label>
          <textarea name="description" rows="3"></textarea>
        </div>
        <div class="field">
          <label>Display Sequence</label>
          <input name="seq" type="number" min="0" value="0">
        </div>
        <div class="field">
          <label>Features</label>
          <span id="features-select"></span>
          <input id="features" type="hidden" name="features" value="">
        </div>
        <div class="actionbar">
          <input type="submit" name="submit" value="Add Tool / Service">
        </div>
      </div>
    </section>
  </form>

  <section>
    <button onclick="setTimeout(()=>document.getElementById('name-field').focus())" type="button">
      <label for="toggle-modal">+ Add Tool / Service</label>
    </button>

    <div class="list-header">
      <h3>List of tools</h3>
      <span style="margin-left:auto;display:flex;align-items:center">
        <?=$view->paginationLinks($pagination)?>
      </span>
    </div>
    <?php foreach($tools as $index => $tool): ?>

    <div class="list-item">
      <div class="col1" style="min-width:40px;"><?=($pagination->offset + $index + 1) . '. '?></div>
      <div class="col2"><a href="admin/tools/edit/<?=$tool->id?>"><?=$tool->name?></a></div>
      <div class="actions" style="margin-left:auto;display:flex;align-items:center">
        <a class="btn btn-link" href="<?=$tool->website?>" target="_blank">
          <i class="fa fa-globe blue" aria-hidden="true"></i>
        </a>
        <a class="btn btn-link" href="admin/tools/edit/<?=$tool->id?>">
          <i class="fa fa-pencil" aria-hidden="true"></i>
        </a>
        <button type="button" class="btn-delete">
          <i class="fa fa-trash red" aria-hidden="true"></i>
        </button>
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
    <label>
      <input type="checkbox" onchange="document.body.classList.toggle('showdelete');">
      <span>&nbsp;Show Delete</span>
    </label>
  </section>

  <script src="js/pure-select.min.js"></script>

  <script>
    window.App = {

      Option: function (value, label) {
        this.el = document.createElement('option');
        this.el.value = value;
        this.el.innerHTML = label;
      },

      get: function(url, onSuccess) {
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

      goto: function(url) {
        window.location.href = url;
      },

      fetchSubCategories: function(category_id) {
        console.log('Hi, fetching sub-categories!');
        this.get('admin/tools?ajax=getSubCategories&id=' + category_id, function(optionsData) {
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

    class TagSelect extends SelectPure {
      _sortOptions(event) {
        this._options.forEach(_option => {
          if (!_option.get().textContent.toLowerCase().match(event.target.value.toLowerCase())) {
            _option.addClass("select-pure__option--hidden");
            return;
          }
          _option.removeClass("select-pure__option--hidden");
        });
      }
    }

    App.get('admin/tools?ajax=getFeatures', function(features) {
      // console.log('Ajax Resp - Features =', features);
      App.featuresSelect = new TagSelect('#features-select', {
        options: features,
        value: [],
        multiple: true,
        autocomplete: true,
        icon: "fa fa-times",
        onChange: function(value) {
          console.log(value);
          let elSelect = App.featuresSelect._parent.get();
          elSelect.nextElementSibling.value = JSON.stringify(value);
        }
      });
    });

  </script>

</div>
<?php

include $app->env->rootPath . '/footer.php';


$_SESSION[$app->id] = $app->state;


// <ul>
//   <li>Unbounce</li>
//   <li>LeadPages</li>
//   <li>Wordpress</li>
//   <li>KickoffLabs</li>
//   <li>Upviral</li>
//   <li>Infusionsoft</li>
//   <li>Clickfunnels</li>
//   <li>OntraPort</li>
//   <li>Campaigner</li>
//   <li>Zapier</li>
//   <li>Heap</li>
//   <li>Segment</li>
//   <li>WebinarJam</li>
//   <li>StealthSeminar</li>
// </ul>