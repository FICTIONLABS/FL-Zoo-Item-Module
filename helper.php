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

    // Get Items

    public function getItems() {

        // set vars

        $join_search    = $join_category = $join_tag = '';
        $result         = $element = $value = $compare = $mode = $query_where = $query_where_extra = array();
        $apps           = $this->params->get('applications', array());
        $types          = $this->params->get('types', array());
        $limit          = $this->params->get('count', 1);
        $condition      = $this->params->get('elements_condition');
        $elements       = $this->params->get('elements');

        $db = JFactory::getDbo();

        foreach ($elements as $key => $row) {

            $value = strtolower($row->element_value);

            if ($value != '') { // check empty element value

                if ($row->element_compare == 'LIKE' || $row->element_compare == 'NOT LIKE') {
                    $value = '%'.$value.'%';
                }

                $row->element_compare = str_replace(array('lt', 'gt'), array('<', '>'), $row->element_compare);

                if ($row->element_mode == 'mode_d') { // set date format
                    $row->element_value = JFactory::getDate($row->element_value)->toSQL();
                }

                if (strpos($row->element_id, '_') !== FALSE) { // core elements conditions

                    if ($row->element_id == '_itemtag') { // search from tag table

                        $join_tag = " LEFT JOIN ".ZOO_TABLE_TAG." AS c ON a.id = c.item_id";
                        $query_where[] = "c.name ".$row->element_compare." ".$db->quote($value)."";

                    } elseif($row->element_id == '_itemcategory') { // search from category table

                        if ($row->element_value == '{CATEGORY_ID}') {
                            $value = JFactory::getApplication()->input->get('category_id', 0);
                        }

                        $join_category = " LEFT JOIN ".ZOO_TABLE_CATEGORY_ITEM." AS d ON a.id = d.item_id";
                        $query_where[] = "d.category_id ".$row->element_compare." ".$db->quote($value)."";

                    } else { // search from item table

                        $query_where[] = "a.".str_replace('_item', '', $row->element_id)." ".$row->element_compare." ".$db->quote($value);

                    }
                } else { // custom elements condition

                    if ($row->element_value == '{BIRTHDAY}') {
                        $query_where[] = "(b.element_id = ".$db->quote($row->element_id)." AND MONTH(b.value) = MONTH(CURDATE()) AND DAYOFMONTH(b.value) = DAYOFMONTH(CURDATE()))";
                    }

                    if ($row->element_value == '{NOW}') {
                        $value = JFactory::getDate()->toSQL();
                    }

                    $join_search    = " LEFT JOIN ".ZOO_TABLE_SEARCH." AS b ON a.id = b.item_id";
                    $query_where[]  = "(b.element_id = ".$db->quote($row->element_id)." AND b.value ".$row->element_compare." ".$db->quote($value).")";

                }
            }
        }

        if (!empty($apps)) { // apps conditions
            $query_where_extra[] = " AND a.application_id IN (".implode(',', $apps).")";
        }

        if (!empty($types)) { // types conditions
            $query_where_extra[] = " AND a.type IN (".implode(',', $db->quote($types)).")";
        }

        // get item ordering
        $orderby = $this->params->get('order');
        list($joinOrder, $order) = $this->getItemOrder($orderby, $ignore_order_priority);

        // query
        $select     = "DISTINCT a.*";
        $from       = ZOO_TABLE_ITEM." AS a"
                    .$join_search
                    .$join_category
                    .$join_tag
                    .($joinOrder ? $joinOrder : "");
        $conditions = implode(' '.$condition.' ', $query_where).implode('', $query_where_extra);

        $result = $this->app->table->item->all(compact('select', 'from', 'conditions', 'order', 'limit'));

        return $result;
    }

    // Get Item Order

    protected function getItemOrder($order, $ignore_order_priority = false) {
        $result = array();

        if (in_array('_ignore_priority', $order)) {
            $ignore_order_priority = true;
            unset($order['_ignore_priority']);
        }

        // trigger order event
        $this->app->event->dispatcher->notify($this->app->event->create($order, 'item:changeorder'));

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
                    $result[1] = $reversed == 'ASC' ? "a.$var+0<>0 DESC, a.$var+0, a.$var" : "a.$var+0<>0, a.$var+0 DESC, a.$var DESC";
                } else {
                    $result[1] = $reversed == 'ASC' ? "a.$var" : "a.$var DESC";
                }

            }
        }

        // else order by elements
        if (!isset($result[1])) {
            $result[0] = " LEFT JOIN ".ZOO_TABLE_SEARCH." AS s ON a.id = s.item_id AND s.element_id IN ('".implode("', '", $order)."')";
            if ($alphanumeric) {
                $result[1] = $reversed == 'ASC' ? "ISNULL(s.value), s.value+0<>0 DESC, s.value+0, s.value" : "s.value+0<>0, s.value+0 DESC, s.value DESC";
            } else {
                $result[1] = $reversed == 'ASC' ? "s.value" : "s.value DESC";
            }
        }

        // If there wasn't _ignore_priority in the order array, prefix priority
        if (!$ignore_order_priority) {
            $result[1] = $result[1] ? 'a.priority DESC, ' . $result[1] : 'a.priority DESC';
        }

        // trigger init event
        $this->app->event->dispatcher->notify($this->app->event->create($order, 'item:orderquery', array('result' => &$result)));

        return $result;

    }

}