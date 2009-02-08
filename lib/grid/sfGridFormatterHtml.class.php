<?php

/*
 * This file is part of the symfony package.
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class sfGridFormatterHtml implements sfGridFormatterInterface
{
  protected
    $grid     = null,
    $row      = null,
    $cursor   = 0,
    $uri      = null,
    $sortable = array();
    
  static public function indent($code, $levels)
  {
    $lines = explode("\n", $code);
    foreach ($lines as &$line)
    {
      $line = str_repeat('  ', $levels) . $line;
    }
    return implode("\n", $lines);
  }
    
  public function __construct(sfGrid $grid)
  {
    $this->grid = $grid;
    
    if (count($this) > 0)
    {
      $this->row = new sfGridFormatterHtmlRow($grid, 0);
    }
  }
    
  public function render()
  {
    return $this->renderHead().$this->renderFoot().$this->renderBody();
  }
  
  public function renderHead()
  {
    $html = "<thead>\n<tr>\n";
    
    foreach ($this->grid->getColumns() as $column)
    {
      $html .= "  " . $this->renderColumnHead($column) . "\n";
    }
    
    return $html . "</tr>\n</thead>\n";
  }
  
  public function renderPager()
  {
    $uri = $this->grid->getUri();
    if (empty($uri))
    {
      throw new LogicException('Please specify a URI with sfGrid::setUri() before rendering the pager');
    }
    
    $pager = $this->grid->getPager();
    $html = "<div>\n";
  
    if ($pager->hasFirstPage())
    {
      $html .= "  <a href=\"" . $this->makeUri($uri, array('page' => $pager->getFirstPage())) . "\">|&laquo;</a>\n";
    }
    if ($pager->hasPreviousPage())
    {
      $html .= "  <a href=\"" . $this->makeUri($uri, array('page' => $pager->getPreviousPage())) . "\">&laquo;</a>\n";
    }
    foreach ($pager as $page)
    {
      if ($page == $pager->getPage())
      {
        $html .= "  " . $page . "\n";
      }
      else
      {
        $html .= "  <a href=\"" . $this->makeUri($uri, array('page' => $page)) . "\">" . $page . "</a>\n";
      }
    }
    if ($pager->hasNextPage())
    {
      $html .= "  <a href=\"" . $this->makeUri($uri, array('page' => $pager->getNextPage())) . "\">&raquo;</a>\n";
    }
    if ($pager->hasLastPage())
    {
      $html .= "  <a href=\"" . $this->makeUri($uri, array('page' => $pager->getLastPage())) . "\">&raquo;|</a>\n";
    }
    
    return $html . "</div>\n";
  }
  
  public function renderFoot()
  {
    $pager = $this->grid->getPager();
    $html = $pager->hasToPaginate() ? "\n".self::indent($this->renderPager(), 2) : '';
    
    $html = "<tfoot>\n<tr>\n";
    $html .= "  <th colspan=\"".count($this->grid->getColumns())."\">";
    if ($pager->hasToPaginate())
    {
      $html .= "\n".self::indent($this->renderPager(), 2);
    }
    $html .= "\n    ".$pager->getRecordCount()." results";
    if ($pager->hasToPaginate())
    {
      $html .= " (page ".$pager->getPage()." of ".$pager->getPageCount().")"; 
    } 
    
    
    return $html."\n  </th>\n</tr>\n</tfoot>\n";
  }
  
  public function renderBody()
  {
    $html = "<tbody>\n";
    
    foreach ($this as $row)
    {
      $html .= $row->render();
    }
    
    return $html . "</tbody>\n";
  }
  
  public function renderColumnHead($column)
  {
    $widget = $this->grid->getWidget($column);
    $html = ucfirst($column);
    
    if (in_array($column, $this->grid->getSortable()))
    {
      $uri = $this->grid->getUri();
	    if (empty($uri))
	    {
	      throw new LogicException('Please specify a URI with sfGrid::setUri() before rendering the pager');
	    }
	    
	    // the new order is the inverse order of the current order in the grid,
	    // if the current sort column is this column
	    $order = $this->grid->getSortColumn() == $column 
	       ? ($this->grid->getSortOrder() == sfGrid::ASC ? 'desc' : 'asc')
	       : 'asc';
	    
	    // build the HTML with a class attribute sort_asc or sort_desc, if the
	    // column is currently being sorted
	    $html = sprintf("<a %shref=\"%s\">%s</a>",
	       $this->grid->getSortColumn() == $column 
	           ? 'class="sort_' . ($order == 'asc' ? 'desc' : 'asc') . '" '
	           : '',
	       $this->makeUri($uri, array('sort' => $column, 'sort_order' => $order)), 
	       $html);
    }
    
    return "<th>" . $html . "</th>";
  }
  
  public function current()
  {
    $this->row->initialize($this->grid, $this->cursor);
    
    return $this->row;
  }
  
  public function next()
  {
    ++$this->cursor;
  }
  
  public function key()
  {
    return $this->cursor;
  }
  
  public function rewind()
  {
    $this->cursor = 0;
  }
  
  public function valid()
  {
    return $this->cursor < count($this);
  }
  
  public function count()
  {
    return count($this->grid);
  }
  
  protected function makeUri($uri, array $params)
  {
    // split the uri
    $uri = explode('?', $uri);
    
    // extract the query string
    $values = array();
    if (count($uri) > 1)
    {
      $query = explode('#', $uri[1]);
      parse_str($query[0], $values);
    }
    $params = array_merge($values, $params);
    
    // build the new uri
    return $uri[0] . '?' . http_build_query($params, '', '&');
  }
}
