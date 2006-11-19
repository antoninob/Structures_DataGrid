<?php
/**
 * HTML Table Rendering Driver
 * 
 * <pre>
 * +----------------------------------------------------------------------+
 * | PHP version 4                                                        |
 * +----------------------------------------------------------------------+
 * | Copyright (c) 1997-2005 The PHP Group                                |
 * +----------------------------------------------------------------------+
 * | This source file is subject to version 2.0 of the PHP license,       |
 * | that is bundled with this package in the file LICENSE, and is        |
 * | available through the world-wide-web at                              |
 * | http://www.php.net/license/2_02.txt.                                 |
 * | If you did not receive a copy of the PHP license and are unable to   |
 * | obtain it through the world-wide-web, please send a note to          |
 * | license@php.net so we can mail you a copy immediately.               |
 * +----------------------------------------------------------------------+
 * | Authors: Andrew Nagy <asnagy@webitecture.org>                        |
 * |          Olivier Guilyardi <olivier@samalyse.com>                    |
 * |          Mark Wiesemann <wiesemann@php.net>                          |
 * +----------------------------------------------------------------------+
 * </pre>
 *
 * CSV file id: $Id$
 * 
 * @version  $Revision$
 * @package  Structures_DataGrid_Renderer_HTMLTable
 * @category Structures
 */

require_once 'Structures/DataGrid/Renderer.php';
require_once 'HTML/Table.php';

/**
 * HTML Table Rendering Driver
 *
 * Driver for rendering the DataGrid as an HTMLTable
 *
 * SUPPORTED OPTIONS:
 *
 * - evenRowAttributes:   (array)  An associative array containing each attribute
 *                                 of the even rows.
 *                                 (default: array())
 * - oddRowAttributes:    (array)  An associative array containing each attribute
 *                                 of the odd rows.
 *                                 (default: array())
 * - emptyRowAttributes:  (array)  An associative array containing the attributes
 *                                 for empty rows.
 *                                 (default: array())
 * - selfPath:            (string) The complete path for sorting and paging links.
 *                                 (default: $_SERVER['PHP_SELF'])
 * - sortIconASC:         (string) The icon to define that sorting is currently
 *                                 ascending. Can be text or HTML to define an image.
 *                                 (default: '')
 * - sortIconDESC:        (string) The icon to define that sorting is currently
 *                                 descending. Can be text or HTML to define an image.
 *                                 (default: '')
 * - headerAttributes:    (array)  Attributes for the header row. This is an array
 *                                 of the form: array(attribute => value, ...)
 *                                 (default: array())
 * - columnAttributes:    (array)  Column cells attributes. This is an array of
 *                                 the form:
 *                                 array(fieldName => array(attribute => value, ...) ...)
 *                                 (default: array())
 * - convertEntities:     (bool)   Whether or not to convert html entities.
 *                                 This calls htmlspecialchars(). 
 *                                 (default: true)
 * - sortingResetsPaging: (bool)   Whether sorting HTTP queries reset paging.  
 *                                 (default: true)
 *                  
 * SUPPORTED OPERATION MODES:
 *
 * - Container Support: yes
 * - Output Buffering:  yes
 * - Direct Rendering:  no
 *
 * @version  $Revision$
 * @author   Andrew S. Nagy <asnagy@webitecture.org>
 * @author   Olivier Guilyardi <olivier@samalyse.com>
 * @author   Mark Wiesemann <wiesemann@php.net>
 * @access   public
 * @package  Structures_DataGrid_Renderer_HTMLTable
 * @category Structures
 */
class Structures_DataGrid_Renderer_HTMLTable extends Structures_DataGrid_Renderer
{
    /**
     * Rendering container
     * @var object HTML_Table object
     * @access protected
     */
    var $_table;
    
    /**
     * The html_table_storage object for the table header
     * @var object HTML_Table_Storage
     */
    var $_tableHeader;

    /**
     * The html_table_storage object for the table body
     * @var object HTML_Table_Storage
     */
    var $_tableBody;

    /**
     * The body row index to start rendering at
     * @var int
     */
    var $_bodyStartRow;
    
    /**
     * Constructor
     *
     * Build default values
     *
     * @access  public
     */
    function Structures_DataGrid_Renderer_HTMLTable()
    {
        parent::Structures_DataGrid_Renderer();
        $this->_addDefaultOptions(
            array(
                'evenRowAttributes'   => array(),
                'oddRowAttributes'    => array(),
                'emptyRowAttributes'  => array(),
                'selfPath'            => htmlspecialchars($_SERVER['PHP_SELF']),
                'sortIconASC'         => '',
                'sortIconDESC'        => '',
                'columnAttributes'    => array(),
                'headerAttributes'    => array(),
                'convertEntities'     => true,
                'sortingResetsPaging' => true,
            )
        );
    }

    /**
     * Attach an already instantiated HTML_Table object
     *
     * @var object HTML_Table object
     * @return mixed  True or PEAR_Error
     * @access public
     */
    function setContainer(&$table)
    {
        $this->_table =& $table;
        return true;
    }
    
    /**
     * Return the currently used HTML_Table object
     *
     * @return object HTML_Table (reference to) or PEAR_Error
     * @access public
     */
    function &getContainer()
    {
        isset($this->_table) or $this->init();
        return $this->_table;
    }
    
    /**
     * Instantiate the HTML_Table container if needed, and set it up
     * 
     * @access protected
     */
    function init()
    {
        if (!isset($this->_table)) {
            $this->_table = new HTML_Table(null, null, true);
        }

        $this->_tableHeader =& $this->_table->getHeader();
        $this->_tableBody =& $this->_table->getBody();

        $this->_bodyStartRow = $this->_tableBody->getRowCount();
    }

    /**
     * Set a table attribute
     *
     * @deprecated Use the HTML_Table constructor directly instead
     * @access public
     * @param  string   $attr    The name of the attribute.
     * @param  string   $value   The value of the attribute.
     */
    function setTableAttribute($attr, $value)
    {
        if (is_null($this->_table)) {
            $this->init();
        }
        $this->_table->updateAttributes(array($attr => $value));
    }

    /**
     * Define the table's header row attrbiutes
     *
     * @access public
     * @param  array     $attribs   The attributes for the table header row.
     */
    function setTableHeaderAttributes($attribs)
    {
        $this->_options['headerAttributes'] = $attribs;
    }

    /**
     * Define the table's odd row attributes
     *
     * @access public
     * @param  array    $attribs    The associative array of attributes for the
     *                              odd table row.
     * @see HTML_Table::setCellAttributes
     */
    function setTableOddRowAttributes($attribs)
    {
        $this->_options['oddRowAttributes'] = $attribs;
    }

    /**
     * Define the table's even row attributes
     *
     * @access public
     * @param  array    $attribs    The associative array of attributes for the
     *                              even table row.
     * @see HTML_Table::setCellAttributes
     */
    function setTableEvenRowAttributes($attribs)
    {
        $this->_options['evenRowAttributes'] = $attribs;
    }

    /**
     * Define the table's autofill value.  This value appears only in an empty
     * table cell.
     *
     * @access public
     * @param  string    $value     The value to use for empty cells.
     */
    function setAutoFill($value)
    {
        if (is_null($this->_table)) {
            $this->init();
        }
        $this->_tableBody->setAutoFill($value);
    }

    /**
     * In order for the DataGrid to render "Empty Rows" to allow for uniformity
     * across pages with varying results, set this option to true.  An example
     * of this would be when you have 11 results and have the DataGrid show 10 
     * records per page. The last page will only show one row in the table, 
     * unless this option is turned on in which it will render 10 rows, 9 of 
     * which will be empty.
     *
     * @access public
     * @param  bool      $value          A boolean value to determine whether or
     *                                   not to display the empty rows.
     * @param  array     $attributes     The empty row attributes defined in an 
     *                                   array.
     */
    function allowEmptyRows($value, $attributes = array())
    {
        $this->_options['fillWithEmptyRows'] = (bool)$value;
        $this->_options['emptyRowAttributes'] = $attributes;
    }

    /**
     * Determines whether or not to use the Header
     *
     * @deprecated Use the "buildHeader" option instead
     * @access  public
     * @param   bool    $bool   value to determine to use the header or not.
     */
    function useHeader($bool)
    {
        $this->_options['buildHeader'] = (bool)$bool;
    }

    /**
     * Add custom GET variables to the generated links
     *
     * This method adds the provided variables to the paging and sorting
     * links. The variable values are automatically url encoded.
     *
     * @deprecated Use the "extraVars" option instead
     * @param   array   $vars   Array of the form (key => value, ...) 
     * @access  public
     * @return  void
     */
    function setExtraVars($vars)
    {
        $this->_options['extraVars'] = $vars;
    }

    /**
     * Exclude GET variables from the generated links
     *
     * This method excludes the provided variables from the paging and sorting
     * links. This is helpful when using variables that determine what page to
     * show such as an 'action' variable, etc.
     * 
     * @deprecated Use the "excludeVars" option instead
     * @param   array       $vars       An array of variables to remove
     * @access  public
     * @return  void
     */
    function excludeVars($vars)
    {
        $this->_options['excludeVars'] = $vars;
    }    

    /**
     * Generates the HTML for the DataGrid
     *
     * @deprecated Use getOutput() instead.
     * @access  public
     * @return  string      The HTML of the DataGrid
     * @see Structures_DataGrid_Renderer::getOutput
     */
    function toHTML()
    {
        return $this->getOutput();
    } 

    /**
     * Gets the HTML_Table object for the DataGrid
     *
     * @deprecated Use getContainer() instead.
     * @access  public
     * @return  object HTML_Table   The HTML Table object for the DataGrid
     */
    function &getTable()
    {
        return $this->_table;
    }   

    /**
     * Handles building the header of the DataGrid
     *
     * @param   array $columns Columns' fields names and labels 
     * @access  protected
     * @return  void
     * @see     http://www.php.net/manual/en/function.http-build-query.php
     */
    function buildHeader(&$columns)
    {
        $row = $this->_tableHeader->getRowCount();

        foreach ($columns as $col => $spec) {
            $field = $spec['field'];
            $label = $spec['label'];

            // Define Content
            if (in_array($field, $this->_sortableFields)) {
                
                // Determine next sort direction and current sort icon
                reset($this->_currentSort);
                if (list($currentField,$currentDirection) = each($this->_currentSort)
                    and $currentField == $field) {
                    if ($currentDirection == 'ASC') {
                        $icon = $this->_options['sortIconASC'];
                        $direction = 'DESC';
                    } else {
                        $icon = $this->_options['sortIconDESC'];
                        $direction = 'ASC';
                    }
                } else {
                    $icon = '';
                    $direction = 'ASC';
                }

                // Build HTTP query
                $extra = array('page' => $this->_options['sortingResetsPaging'] 
                                         ? 1 : $this->_page);
                $query = $this->_buildSortingHttpQuery($field, $direction, true, $extra);

                // Build Link URL
                $url = $this->_options['selfPath'] . '?' . $query;

                // Build HTML Link
                $str = "<a href=\"$url\">$label$icon</a>";
            } else {
                $str = $label;
            }

            // Print Content to HTML_Table
            $this->_tableHeader->setHeaderContents($row, $col, $str);
            if (isset($this->_options['columnAttributes'][$field])) {
                $this->_tableHeader->setCellAttributes($row, $col, $this->_options['columnAttributes'][$field]);
            }
        }
        if (count($this->_options['headerAttributes']) > 0) {
            $this->_tableHeader->setRowAttributes($row, $this->_options['headerAttributes'], false);
        }
    }

    /**
     * Build a body row
     *
     * @param int   $index Row index (zero-based)
     * @param array $data  Record data. 
     *                     Structure: array(0 => <value0>, 1 => <value1>, ...)
     * @return void
     * @access protected
     */
    function buildRow($index, $data)
    {
        $outputRow = $this->_bodyStartRow + $index;
        foreach ($data as $col => $value) {
            $field = $this->_columns[$col]['field'];

            // Right-align the content if it is numeric
            $attributes = ($this->_options['numberAlign'] and is_numeric($value)) 
                        ? array('align' => 'right')
                        : array();

            // merge auto-aligned and column attributes
            if (isset($this->_options['columnAttributes'][$field])) {
                $attributes = array_merge($attributes,
                                          $this->_options['columnAttributes'][$field]);
            }

            // Set content in HTML_Table
            $this->_tableBody->setCellContents($outputRow, $col, $value);
            if ($attributes) {
                $this->_tableBody->setCellAttributes($outputRow, $col, $attributes);
            }
        }
    }
   
    /**
     * Build an empty row
     *
     * This method will only be called if the "fillWithEmptyRows" option is
     * enabled.
     * 
     * @param int   $index Row index (zero-based)
     * @return void
     * @abstract
     */
    function buildEmptyRow($index)
    {
        $outputRow = $this->_bodyStartRow + $index;
        for ($col = 0; $col < $this->_columnsNum; $col++) {
            $this->_tableBody->setCellAttributes($outputRow, $col, $this->_options['emptyRowAttributes']);
            $this->_tableBody->setCellContents($outputRow, $col, '&nbsp;');
        }
    }
    
    /**
     * Default formatter for all cells
     * 
     * @param string  Cell value 
     * @return string Formatted cell value
     * @access protected
     */
    function defaultCellFormatter($value)
    {
        return $this->_options['convertEntities']
               ? htmlspecialchars($value, ENT_COMPAT, $this->_options['encoding'])
               : $value;
    }

    /**
     * Finish building the datagrid.
     *
     * @access  protected
     * @return  void
     */
    function finalize()
    {
        // Define alternating row attributes
        if ($this->_options['evenRowAttributes'] 
            or $this->_options['oddRowAttributes']) {

            $this->_tableBody->altRowAttributes(
                0,
                $this->_options['evenRowAttributes'],
                $this->_options['oddRowAttributes'],
                true
            );
        }
    }
    
    /**
     * Retrieve output from the container object 
     *
     * @return mixed Output
     * @access protected
     */
    function flatten()
    {
        return $this->_table->toHTML();
    }

    /**
     * Handles the building of the page list for the DataGrid in HTML.
     * 
     * This method uses the HTML::Pager class
     *
     * Useful options (See Pager's documentation for more):
     * mode:       The mode of pager to use
     * separator:  The string to use to separate each page link
     * prevImg:    The string for the previous page link
     * nextImg:    The string for the forward page link
     * delta:      The number of pages to display before and
     *             after the current page
     *
     * @deprecated Use Structures_DataGrid_Renderer_Pager instead
     * @access  public
     * @param   array  $options        Array of HTML::Pager options
     * @return  string                 The HTML for the page links
     * @see     HTML::Pager
     */
    function getPaging($options = array())
    {
        // This is a BC workaround for the old version of this method
        if (is_string($options)) {
            $argsNum = func_num_args(); 
            $args = func_get_args();
            $options = array();

            for ($i = 0; $i < $argsNum; $i++) {
                switch ($i) {
                    case 0: $options['mode'] = $args[$i]; break;
                    case 1: $options['separator'] = $args[$i]; break;
                    case 2: $options['prevImg'] = $args[$i]; break;  
                    case 3: $options['nextImg'] = $args[$i]; break;  
                    case 4: $options['delta'] = $args[$i]; break;  
                    case 5: $options = array_merge($options, $args[$i]); break;  
                }
            }
        }
       
    
        // Load and get output from the Pager rendering driver
        $driver =& Structures_DataGrid::loadDriver('Structures_DataGrid_Renderer_Pager');
        $driver->setupAs($this, $options);
        return $driver->getOutput();
    }

}

/* vim: set expandtab tabstop=4 shiftwidth=4: */
?>
