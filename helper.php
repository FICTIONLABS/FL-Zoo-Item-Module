<?php

/**
 * @package   FL Zoo Item Module for Zoo
 * @author    Дмитрий Васюков http://fictionlabs.ru
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

// No direct access
defined( '_JEXEC' ) or die;

require_once JPATH_ADMINISTRATOR . '/components/com_zoo/config.php';

class modFlZooItemHelper extends AppHelper
{      
    public function __construct($app, $params) {

        $this->params = $params;

        parent::__construct($app);
    }

    // Get Item

    public function getItems() {

        $join_search    = $join_category = $join_tag = '';
        $tags           = array('{NOW}, {BIRTHDAY}');
        $result         = $element = $value = $compare = $mode = $query_where = $query_where_extra = array();
        $apps           = $this->params->get('applications', array());
        $types          = $this->params->get('types', array());
        $limit          = $this->params->get('count', 1);
        $condition      = $this->params->get('elements_condition');
        // $order          = $this->params->get('order');

        // $elements = $this->groupByKey(json_decode($this->params->get('elements'), true));
        $elements = $this->params->get('elements');

        // $order = $this->getItemOrder($order);

        $db = JFactory::getDbo();
        
        $lastElement    = end($elements);
        $lastElementKey = key($elements);

        foreach ($elements as $key => $row) {

            $row->element_compare = str_replace(array('lt', 'gt'), array('<', '>'), $row->element_compare);

            if ($row->element_mode == 'mode_d' && !in_array($row->element_value, $tags)) {
                $row->element_value = JFactory::getDate($row->element_value)->toSQL(); // get date from value
            }

            switch ($row->element_value) {
                case '{BIRTHDAY}':
                    $join_search = " LEFT JOIN ".ZOO_TABLE_SEARCH." AS b ON a.id = b.item_id";
                    $query_where[] = "(b.element_id = ".$db->quote($row->element_id)." AND MONTH(b.value) = MONTH(CURDATE()) AND DAYOFMONTH(b.value) = DAYOFMONTH(CURDATE()))";
                    break;

                case '{NOW}':
                    $value = JFactory::getDate()->toSQL();
                    $join_search = " LEFT JOIN ".ZOO_TABLE_SEARCH." AS b ON a.id = b.item_id";
                    $query_where[] = "(b.element_id = ".$db->quote($row->element_id)." AND b.value ".$row->element_compare." ".$db->quote($value).")";
                    break;

                default:
                    $value = strtolower($row->element_value);
                    if (strpos($row->element_id, '_') !== FALSE) {
                        if ($row->element_id == '_itemtag') {
                            $join_tag = " LEFT JOIN ".ZOO_TABLE_TAG." AS c ON a.id = c.item_id";
                            $query_where[] = "c.name ".$row->element_compare." ".$db->quote($value)."";
                        } elseif($row->element_id == '_itemcategory') {
                            $join_category = " LEFT JOIN ".ZOO_TABLE_CATEGORY_ITEM." AS d ON a.id = d.item_id";
                            $query_where[] = "d.category_id ".$row->element_compare." ".$db->quote($value)."";
                        } else {
                            $query_where[] = "a.".str_replace('_item', '', $row->element_id)." ".$row->element_compare." ".$db->quote($value);
                        }
                    } else {
                        $join_search = " LEFT JOIN ".ZOO_TABLE_SEARCH." AS b ON a.id = b.item_id";
                        $query_where[] = "(b.element_id = ".$db->quote($row->element_id)." AND b.value ".$row->element_compare." ".$db->quote($value).")";
                    }
                    break;
            }         

            if($key != $lastElementKey) {
                $query_where[] = " ".strtoupper($condition)." ";
            }
        }

        if (!empty($apps)) {
            $query_where_extra[] = " AND a.application_id IN (".implode(',', $apps).")";
        }

        if (!empty($types)) {
            $query_where_extra[] = " AND a.type IN (".implode(',', $db->quote($types)).")";
        }

        $query = "SELECT a.id"
                ." FROM ".ZOO_TABLE_ITEM." AS a"
                .$join_search
                .$join_category
                .$join_tag
                ." WHERE (%s)%s"
                ." AND a.searchable=1"
                ." GROUP BY a.id"
                // .($order ? " ORDER BY " . $order : "")
                ." LIMIT ".$limit;

        $query = sprintf($query, implode('', $query_where), implode('', $query_where_extra));

        $db->setQuery($query);

        $result = $this->getItemObjects($db->loadObjectList());

        return $result;
    }

    // Get Array Grouped By Key

    // public function groupByKey($array) {
    //     $result = array();

    //     foreach ($array as $textKey => $sub) 
    //     {
    //         foreach ($sub as $k => $v) 
    //         {
    //             $result[$k][$textKey] = $v;
    //         }
    //     }
    //     return $result;
    // }

    // Get Item Order

    protected function getItemOrder($order, $ignore_order_priority = false) {

        $result = array();

        if (in_array('_ignore_priority', $order)) {
            $ignore_order_priority = true;
            unset($order['_ignore_priority']);
        }

        // remove empty and duplicate values
        $order = array_unique(array_filter($order));

        // if random return immediately
        if (in_array('_random', $order)) {
            $result = 'RAND()';
            return $result;
        }

        // get order dir
        if (($index = array_search('_reversed', $order)) !== false) {
            $reversed = 'DESC';
            unset($order[$index]);
        } else {
            $reversed = 'ASC';
        }

        // get ordering type
        $alphanumeric = false;
        if (($index = array_search('_alphanumeric', $order)) !== false) {
            $alphanumeric = true;
            unset($order[$index]);
        }

        // set default ordering attribute
        if (empty($order)) {
            $order[] = '_itemname';
        }

        // if there is a none core element present, ordering will only take place for those elements
        if (count($order) > 1) {
            $order = array_filter($order, create_function('$a', 'return strpos($a, "_item") === false;'));
        }

        // order by core attribute
        foreach ($order as $element) {

            if (strpos($element, '_item') === 0) {
                $var = str_replace('_item', '', $element);
                if ($alphanumeric) {
                    $result = $reversed == 'ASC' ? "$var+0<>0 DESC, $var+0, $var" : "$var+0<>0, $var+0 DESC, $var DESC";
                } else {
                    $result = $reversed == 'ASC' ? "$var" : "$var DESC";
                }

            }
        }

        // If there wasn't _ignore_priority in the order array, prefix priority
        if (!$ignore_order_priority) {
            $result = $result ? 'priority DESC, ' . $result : 'priority DESC';
        }

        return $result;

    }

    public function getItemObjects($object) {
        $ids = array();
        if ($object) {
            foreach ($object as $key => $item) {
                $ids[] = $item->id;
            }
        }
        return $this->getZooItemsByIds($ids);
    }

    // Get Zoo Items By Ids

    public function getZooItemsByIds($ids) {
        $ids = array_filter($ids);
        if (empty($ids)) {
            return array();
        }

        $conditions = array(
            'id IN (' . implode(',', $ids) . ')',
        );

        $order = $this->params->get('order');
        $order = $this->getItemOrder($order);

        $result = $this->app->table->item->all(compact('conditions', 'order'));

        return $result;
    }

}