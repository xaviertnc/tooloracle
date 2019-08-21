<?php // Tool Oricle - Front Controller

include $_SERVER['DOCUMENT_ROOT'] . '/bootstrap.php';


DB::connect($app->dbConnection);


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

$pagination = new stdClass();
$pagination->itemsPerPage = 10;
$pagination->page = array_get($_GET, 'page', 1);
$pagination->totalItems = DB::query('tools')->count();
$pagination->offset = ($pagination->page - 1) * $pagination->itemsPerPage;
$pagination->pages = ceil($pagination->totalItems / $pagination->itemsPerPage);

$tools = DB::query('tools')->limit($pagination->offset, $pagination->itemsPerPage)->get();

$categories = DB::select('tool_categories');

$message = array_get($app->state, 'message', null);
unset($app->state['message']);


include $app->rootPath . '/header.php';

?>
<div class="page home">

  <h2>Welcome to TOOL ORACLE</h2>

  <?php if ($message):?>
  <h1><?=$message?></h1>
  <?php endif; ?>

  <section>
    <div class="list-header">
      <span style="margin-left:auto;display:flex;align-items:center">
        <?=$view->paginationLinks($pagination)?>
      </span>
    </div>
    <?php foreach($tools as $index => $tool): ?>

    <div class="list-item">
      <div class="col1" style="min-width:40px;"><?=($pagination->offset + $index + 1) . '. '?></div>
      <div class="col2"><a href="/pages/tool/edit.php?id=<?=$tool->id?>"><?=$tool->name?></a></div>
      <div class="actions" style="margin-left:auto;display:flex;align-items:center">
        <a class="btn btn-link" href="<?=$tool->website?>" target="_blank">
          <i class="fa fa-globe blue" aria-hidden="true"></i>
        </a>
        <a class="btn btn-link" href="/pages/tool/edit.php?id=<?=$tool->id?>">
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

  <script src="/js/pure-select.min.js"></script>

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
        this.get('./?ajax=getSubCategories&id=' + category_id, function(optionsData) {
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

    App.get('./?ajax=getFeatures', function(features) {
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

include $app->rootPath . '/footer.php';


$_SESSION[$app->id] = $app->state;