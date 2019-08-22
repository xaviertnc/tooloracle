<?php

class View {

  public $app;
  public $dent;


  public function __construct($app, $dent = null)
  {
    $this->app = $app;
    $this->dent = $dent ?: '  ';  // OR "\t"
  }


  public function e($str)
  {
    if (is_string($str)) {
      return htmlspecialchars($str, ENT_QUOTES | ENT_IGNORE, "UTF-8", false);
    }
  }


  public function indent($n, $dent)
  {
    return $n ? str_repeat($dent, $n) : '';
  }



  public function indentBlock($text, $indent)
  {
    return implode("\n" . $indent, explode("\n", trim($text)));
  }


  public function pagerLink($baseUri, $page, $icon, $disabled = false)
  {
    $href= "$baseUri?page=$page";
    return '<a class="btn btn-link' . ($disabled ? ' disabled' : '') . '" ' .
     'href="' . $href . '"><i class="fa fa-' . $icon . '" aria-hidden="true"></i></a>';
  }


  public function paginationLinks($pagination)
  {
    $page = $pagination->page;
    $pages = $pagination->pages;
    $uri = $pagination->baseUri;
    return $this->pagerLink($uri, 1, 'fast-backward', $page <= 1) . '&nbsp;' .
      $this->pagerLink($uri, $page > 1 ? $page - 1 : $page, 'backward', $page <= 1) . '&nbsp;&nbsp;' .
      'Page:&nbsp;<input type="text" value="'. $page .'" name="page">&nbsp;of&nbsp;' . $pages . '&nbsp;' .
      $this->pagerLink($uri, $page < $pages ? $page + 1 : $page, 'forward', $page >= $pages) . '&nbsp;' .
      $this->pagerLink($uri, $pages, 'fast-forward', $page >= $pages);
  }

}


$view = new View($app);
