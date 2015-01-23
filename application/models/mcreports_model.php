<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Active Record Models for homework tasks invoked via cron
 *
 */
class Mcreports_model extends CI_Model
{

    function __construct() {
        parent:: __construct();
    }

    /**
     * store all the data we get from mailchimp for a specific autoresponder into the database
     * @param $mcData data from mailchimp for autoresponder
     */
    public function storeDataInDatabase($mcData)  {

        $data = array(
            'mc_id' => $mcData['mc_id'],
            'timestamp' => $mcData['timestamp'],
            'calendar_week' => $mcData['calendar_week'],
            'year' => $mcData['year'],
            'web_id' => $mcData['web_id'],
            'list_id' => $mcData['list_id'],
            'title' => $mcData['title'],
            'type' => $mcData['type'],
            'status' => $mcData['status'],
            'create_time' => $mcData['create_time'],
            'send_time' => $mcData['send_time'],
            'emails_sent' => $mcData['emails_sent'],
            'summary-opens' => $mcData['summary-opens'],
            'summary-clicks' => $mcData['summary-clicks'],
            'summary-unsubscribes' => $mcData['summary-unsubscribes']
        );
        $this->db->insert('mcdata', $data);
    }

    /**
     * if an autoresponder was renamed later, the column in the excel table should be renamed also
     * we always want to display the most recent name in the colum
     */
    public function updateTitlesOfAutoresponders() {

        //native sql query for getting autoresponders with most recent headlines from db
        //SELECT mc_id, title, calendar_week FROM `mcdata` m1 where calendar_week = (SELECT MAX(calendar_week) FROM `mcdata` m2 where m1.mc_id = m2.mc_id) order by create_time asc
        $this->db->select('title, mc_id, calendar_week')->from('mcdata m1')->where('calendar_week = (SELECT MAX(calendar_week) FROM `mcdata` m2 where m1.mc_id = m2.mc_id)')->order_by('create_time', 'asc');
        $query = $this->db->get();
        $result = $query->result_array();

    }

    /**
     * table header data - autoresponder titles
     * @return mixed
     */
    public function loadHeaderData() {
        //$query = $this->db->distinct()->select('title, mc_id')->order_by('create_time', 'asc')->get('mcdata');
        $this->db->select('title, mc_id, calendar_week')->from('mcdata m1')->where('calendar_week = (SELECT MAX(calendar_week) FROM `mcdata` m2 where m1.mc_id = m2.mc_id)')->order_by('create_time', 'asc');
        $query = $this->db->get();
        $result = $query->result_array();
        return $result;
    }

    /**
     * get metadata from backend database to calculate dimensions for excel file etc.
     * @return array
     */
    public function loadMetaData() {

        //get first calendar week with year
        //SELECT distinct `calendar_week`,`year` FROM `mcdata` order by year asc, calendar_week asc
        $query = $this->db->distinct()->select('calendar_week, year')->order_by('year', 'asc')->order_by('calendar_week', 'asc')->get('mcdata');
        $result = $query->result_array();

        $first_calendar_week = $result[0]['calendar_week'];
        $first_year = $result[0]['year'];

        //save the calendar weeks we have to write into the excel file
        $calendar_weeks = array();
        foreach($result as $calendar_week) {
            array_push($calendar_weeks,$calendar_week['year']."_".$calendar_week['calendar_week']);
        }
        //we don't have to write out the first calendar week into the excel file...
        array_shift($calendar_weeks);

        //...  and last calendar week with year
        $query2 = $this->db->distinct()->select('calendar_week, year')->order_by('year', 'desc')->order_by('calendar_week', 'desc')->get('mcdata');
        $result2 = $query2->result_array();

        //count distinct results - how many calendarweeks do we have to print out later?
        $sum_rows = count($result2);

        $query3 = $this->db->distinct()->select('mc_id')->get('mcdata');
        $result3 = $query3->result_array();

        //count newsletters columns
        $sum_columns = count($result3);

        $last_calendar_week = $result2[0]['calendar_week'];
        $last_year = $result2[0]['year'];

        $resultAR = array(
            'sum_columns' => $sum_columns,
            'sum_rows' => $sum_rows,
            'first_calendar_week' => $first_calendar_week,
            'first_year' => $first_year,
            'last_calendar_week' => $last_calendar_week,
            'last_year' => $last_year,
            'calendar_weeks' => $calendar_weeks
        );

        return $resultAR;
    }

    /**
     * load data for specific autoresponder
     * @param $mc_id of autoresponder to fetch data for
     * @return array with meta infos about fetched autoresponder
     */
    public function loadAutoresponderData($mc_id) {

        $query = $this->db->select('*')->where('mc_id', $mc_id)->order_by('year', 'asc')->order_by('calendar_week', 'asc')->get('mcdata');
        $result = $query->result_array();

        //generate surrogate keys for data array
        $keys = array();
        foreach($result as $autoresponder) {
            array_push($keys,$autoresponder['year']."_".$autoresponder['calendar_week']);
        }

        $resultWithKeys = array_combine($keys, $result);
        $first_calendar_week = $result[0]['calendar_week'];
        $first_year = $result[0]['year'];
        $last_calendar_week = $result[sizeof($result)-1]['calendar_week'];
        $last_year = $result[sizeof($result)-1]['year'];

        $resultAR = array (
            'autoresponderData' => $resultWithKeys,
            'first_calendar_week' => $first_calendar_week,
            'first_year' => $first_year,
            'last_calendar_week' => $last_calendar_week,
            'last_year' => $last_year
        );

        return $resultAR;
    }

}