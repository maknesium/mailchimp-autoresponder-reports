<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Class Mcreports - main controller class for generation of reports
 */
class Mcreports extends CI_Controller {

    public function __construct() {
        parent::__construct();
    }

    public function index() {
        $this->load->view('welcome_message');
    }

    /**
     * this method should only be invoked by wget/curl via cron on a weekly or once for initial setup
     * @return string for monitoring purposes
     */
    public function updateDb() {
        //get db model
        $this->load->model('mcreports_model');

        //get data from mailchimp
        $mcApiKey = $this->config->item('Mailchimp_API_KEY');
        $mc = new Mailchimp\Client($mcApiKey);

	    //todo make limit dynamic instead of 1000
        $result = $mc->campaigns->listing(array(),0,1000, "title");
        // convert stdClass to array
        $resultAR = $this->objectToArray($result);

        //store all data in db
        foreach($resultAR['data'] as $child) {
            //fill data array
            $datetime = date('Y-m-d H:i:s');
            $calendar_week = (int)date('W');
            $year = (int)date('Y');

            //we only want autoresponders (new name: automation mails)
            if ($child['type'] != 'auto') continue;

            $data = array(
                'mc_id' => $child['id'],
                'timestamp' => $datetime,
                'calendar_week' => $calendar_week,
                'year' => $year,
                'web_id' => $child['web_id'],
                'list_id' => $child['list_id'],
                'title' => $child['title'],
                'type' => $child['type'],
                'status' => $child['status'],
                'create_time' => $child['create_time'],
                'send_time' => $child['send_time'],
                'emails_sent' => $child['emails_sent'],
                'summary-opens' => $child['summary']['opens'],
                'summary-clicks' => $child['summary']['clicks'],
                'summary-unsubscribes' =>  $child['summary']['unsubscribes']
            );

            //save mailchimpdata to database
            $result = $this->mcreports_model->storeDataInDatabase($data);

            //update titles to latest calendar_week's title
            //$result2 = $this->mcreports_model->updateTitlesOfAutoresponders();
        }
        return ("OK");
    }


    /**
     * "bracet" function for generating and exporting the excel sheet
     */
    public function getExport() {

        $ExcelFilename = '/tmp/MyExcel.xlsx';

        //init excel object
        $objPHPExcel = $this->createExcelSheet();

        //do all report building
        $this->generateReport($objPHPExcel);

        //write excel file to disk
        $this->writeExcelFile($objPHPExcel, $ExcelFilename);

        //redirect and download of finished report
        $this->downloadExcelFile($ExcelFilename);
    }

    /**
     * report generation is done in this method
     * @param $objPHPExcel excel sheet object
     */
    function generateReport($objPHPExcel) {

        //start offset for excel table output
        $xOffset = 1;
        $yOffset = 1;

        //get model for data retrieval
        $this->load->model('mcreports_model');

        //we write in first sheet
        $objPHPExcel->setActiveSheetIndex(0);

        //we set autosize of excel columns
        /*
        foreach(range('A','Z') as $columnID) {
            $objPHPExcel->getActiveSheet()->getColumnDimension($columnID)->setAutoSize(true);
        }
        */

        //set size of first row to auto
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);

        //we load metadata autoresponder metadata
        $metadata = $this->mcreports_model->loadMetaData();

        //we load the titles of the autoresponders
        $ardata = $this->mcreports_model->loadHeaderData();

        $headerColor = 'CFE7F5';
        $sumColor = '07820F';
        $changeColor = '85BCE1';

        $tmpXOffset=$xOffset;
        $tmpYOffset=$yOffset;

        //write SUM header
        $objPHPExcel->getActiveSheet()->getStyleByColumnAndRow($xOffset, $yOffset)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyleByColumnAndRow($xOffset, $yOffset)->getFill()->applyFromArray(array('type' => PHPExcel_Style_Fill::FILL_SOLID,'startcolor' => array('rgb' => $sumColor)));
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($xOffset, $yOffset+1, "Sum / Avg");
        $objPHPExcel->getActiveSheet()->getStyleByColumnAndRow($xOffset, $yOffset+1)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyleByColumnAndRow($xOffset, $yOffset+1)->getFill()->applyFromArray(array('type' => PHPExcel_Style_Fill::FILL_SOLID,'startcolor' => array('rgb' => $sumColor)));
        $objPHPExcel->getActiveSheet()->getStyleByColumnAndRow($xOffset, $yOffset+2)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyleByColumnAndRow($xOffset, $yOffset+2)->getFill()->applyFromArray(array('type' => PHPExcel_Style_Fill::FILL_SOLID,'startcolor' => array('rgb' => $sumColor)));

        $xOffset++;

        //we write the table header
        $index = 1;
        foreach ($ardata as $autoresponder) {
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($xOffset, $yOffset, $index++);
            $objPHPExcel->getActiveSheet()->getStyleByColumnAndRow($xOffset, $yOffset)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyleByColumnAndRow($xOffset, $yOffset)->getFill()->applyFromArray(array('type' => PHPExcel_Style_Fill::FILL_SOLID,'startcolor' => array('rgb' => $headerColor)));

            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($xOffset, $yOffset+1, $autoresponder['mc_id']);
            $objPHPExcel->getActiveSheet()->getStyleByColumnAndRow($xOffset, $yOffset+1)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyleByColumnAndRow($xOffset, $yOffset+1)->getFill()->applyFromArray(array('type' => PHPExcel_Style_Fill::FILL_SOLID,'startcolor' => array('rgb' => $headerColor)));

            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($xOffset, $yOffset+2, $autoresponder['title']);
            $objPHPExcel->getActiveSheet()->getStyleByColumnAndRow($xOffset, $yOffset+2)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyleByColumnAndRow($xOffset, $yOffset+2)->getFill()->applyFromArray(array('type' => PHPExcel_Style_Fill::FILL_SOLID,'startcolor' => array('rgb' => $headerColor)));

            $xOffset++;

            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($xOffset, $yOffset+2, "WoW");
            $objPHPExcel->getActiveSheet()->getStyleByColumnAndRow($xOffset, $yOffset+2)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyleByColumnAndRow($xOffset, $yOffset+2)->getFill()->applyFromArray(array('type' => PHPExcel_Style_Fill::FILL_SOLID,'startcolor' => array('rgb' => $changeColor)));

            $xOffset++;
        }

        //reset table output position
        $xOffset=$tmpXOffset-1;
        $yOffset=$tmpYOffset+3;
        //write calendar week line
        $this->writeCalendarWeekBreakerLine($xOffset, $yOffset, $objPHPExcel, $metadata);

        //we collect data for all autoresponders and order them by calendarweek
        $autoresponderTableData = $this->prepareAutoresponderData($ardata);


        $tmpXOffset=$xOffset;
        $tmpYOffset=$yOffset;
        $initialOffset = $yOffset;
        $kwLine = 0;
        //maintain compatiblity with old php version
        $tmpA = array_values($autoresponderTableData[0]);
        $kpiTextsArray = array_keys($tmpA[0]);

        //output calendar weeks, kpitexts and AR Data
        foreach ($metadata['calendar_weeks'] as $cal_row) {
            //write calendarweek header line: calweek + date

            //write calendar_week breaker line
            $xOffset = $tmpXOffset;
            $yOffset = ((count($kpiTextsArray)+1) * $kwLine) + $initialOffset; //+1 is the kwLine itself!!
            $this->writeCalendarWeekBreakerLine($xOffset, $yOffset, $objPHPExcel, $metadata);
            $kwLine++;

            //write calendar week information
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($xOffset, $yOffset, "Jahr_KW: ".$cal_row);

            //write KPIs
            $yOffset++;
            $j=0;
            //get KPI texts
            //maintain compatiblity with old php version
            $tmpAA = array_values($autoresponderTableData[0]);
            $kpiTexts = array_keys($tmpAA[0]);

            //output kpi texts
            foreach($kpiTexts as $item) {
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($xOffset, $yOffset+$j, $item);
                $j++;
            }

            //init x position
            $xOffset++;
            $tmpYOffset = $yOffset;

            //calculate sum column
            $sums = $this->calculateSumColumn($autoresponderTableData, $kpiTexts, $cal_row);
            //print out sum column
            $this->outputSumColumn($objPHPExcel, $xOffset, $yOffset, $sums);


            //we get ready for autoresponder data output
            $xOffset++;
            $yOffset = $tmpYOffset;

            // foreach autoresponder output its data for current calendar week
            foreach ($autoresponderTableData as $item) {

                //if we have AR data for current calendar_week!
                if (array_key_exists ($cal_row, $item)) {
                    $singleARcolumn = $item[$cal_row]; //cal_row has value of current cal_year key
                } else { //else skip this autoresponder and if we have no AR data for current calendar_week (because Newsletter has been deleted in the past) - we have to SKIP two columns
                    $xOffset += 2; //leave two columns empty
                    continue;
                }

                //we divide between data and change cells
                $dataCells = array_splice($singleARcolumn, 0, 9);
                $changeCells = array_splice($singleARcolumn, 0, 9);

                //then, switch to next column
                //ouput all "change" fields, if we have - else we output empty string

                //first, we output all data fields (9)
                $cellYoffset = 0;
                // we iterate through the discrete values of each autoresponders values and put them into the table
                foreach ($dataCells as $cellValue) {
                    //we output data formatted
                    $this->outputFormatted($objPHPExcel, $cellValue, $xOffset, $yOffset+$cellYoffset);
                    $cellYoffset++;
                }
                //proceed to next column
                $xOffset++;
                $cellYoffset = 0;

                // WoW Column (week-over-week) indicating changes over the last week - if we have no data here, we skip this row and just increment the index
                if (count($changeCells) != 0) {
                    foreach ($changeCells as $cellValue) {
                        //we output data formatted
                        $this->outputFormatted($objPHPExcel, $cellValue, $xOffset, $yOffset+$cellYoffset);
                        $cellYoffset++;
                    }
                }
                //proceed to next column
                $xOffset++;
            }
            //after we have written a complete line of autoresponder data, we have to reset our offsets
            $tmpYOffset++; //one for a spacer line

        }
    }


    /**
     * we have to format some values, like rounding and applying the correct excel style to a cell
     * @param $objPHPExcel excel sheet object
     * @param $cellValue on current position
     * @param $xOffset of current position
     * @param $yOffset of current position
     */
    function outputFormatted($objPHPExcel, $cellValue, $xOffset, $yOffset) {
        //if we have a numeric value, we round it make it also possible to output text
        if (is_numeric($cellValue)) {
            $cellValue = round($cellValue, 4);
        }

        //we have to format certain values between 0 and 1 OR 0 and -1 as percentage
        if (($cellValue < 1) && ($cellValue > 0) || ($cellValue > -1) && ($cellValue < 0)) {
            $objPHPExcel->getActiveSheet()->getStyleByColumnAndRow($xOffset, $yOffset)->getNumberFormat()->applyFromArray(array('code' => PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE_00));
        }

        //if value is not numeric, right align
        if (!is_numeric($cellValue)) {
            $objPHPExcel->getActiveSheet()->getStyleByColumnAndRow($xOffset, $yOffset)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
        }

        //output value
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($xOffset, $yOffset, $cellValue);
    }

    /**
     * calculate sum column for autoresponder calendar week
     * @param $autoresponderTableData all data off all autoresponders for a given calendar week
     * @param $kpiTexts shown on the x axis of excel sheet
     * @param $cal_row current calendar week row
     * @return array with sums for calendar week
     */
    function calculateSumColumn($autoresponderTableData, $kpiTexts, $cal_row) {
        //display sums / avg for current calendarweek in column
        $sums = array(
            'sumSends' => 0,
            'sumOpen' => 0,
            'avgOpenrate' => 0,
            'sumClicks' => 0,
            'avgClickrate' => 0,
            'sumSales' => 0,
            'sumUnsub' => 0,
            'avgUnsubPerSends' => 0,
            'avgUnsubPerOpens' => 0
        );

        //first calculate sums
        foreach ($autoresponderTableData as $item) {
            //sum all items together
            for($x=0; $x < sizeof($kpiTexts); $x++) {
                $kpiTextsSums = array_keys($sums);
                //only if we have sum data for current calendar week
                if (array_key_exists($cal_row, $item)) {
                    $currentCalendarWeek = $item[$cal_row];
                }
                //if a past autoresponder has been deleted we skip it
                if (!isset($currentCalendarWeek)) { continue; }
                $sums[$kpiTextsSums[$x]] += $currentCalendarWeek[$kpiTexts[$x]];
            }
        }

        //then calculate averages
        $sums["avgOpenrate"] = $this->calculateRate($sums["sumSends"], $sums["sumOpen"]);
        $sums["avgClickrate"] = $this->calculateRate($sums["sumSends"], $sums["sumClicks"]);
        $sums["avgUnsubPerSends"] = $this->calculateRate($sums["sumSends"], $sums["sumUnsub"]);
        $sums["avgUnsubPerOpens"] = $this->calculateRate($sums["sumOpen"], $sums["sumUnsub"]);

        return $sums;
    }

    /**
     * print out all sums of kpi texts as first column, centered, formatted as percentage where applicable
     * @param $xOffset of current position
     * @param $yOffset of current position
     * @param $sums calculated before
     */
    function outputSumColumn($objPHPExcel, $xOffset, $yOffset, $sums) {
        $objPHPExcel->getActiveSheet()->getStyleByColumnAndRow($xOffset, $yOffset)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($xOffset, $yOffset++, $sums['sumSends']);
        $objPHPExcel->getActiveSheet()->getStyleByColumnAndRow($xOffset, $yOffset)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($xOffset, $yOffset++, $sums['sumOpen']);
        $objPHPExcel->getActiveSheet()->getStyleByColumnAndRow($xOffset, $yOffset)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($xOffset, $yOffset, $sums['avgOpenrate']);
        $objPHPExcel->getActiveSheet()->getStyleByColumnAndRow($xOffset, $yOffset++)->getNumberFormat()->applyFromArray(array('code' => PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE_00));
        $objPHPExcel->getActiveSheet()->getStyleByColumnAndRow($xOffset, $yOffset)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($xOffset, $yOffset++, $sums['sumClicks']);
        $objPHPExcel->getActiveSheet()->getStyleByColumnAndRow($xOffset, $yOffset)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($xOffset, $yOffset, $sums['avgClickrate']);
        $objPHPExcel->getActiveSheet()->getStyleByColumnAndRow($xOffset, $yOffset++)->getNumberFormat()->applyFromArray(array('code' => PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE_00));
        $objPHPExcel->getActiveSheet()->getStyleByColumnAndRow($xOffset, $yOffset)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($xOffset, $yOffset++, $sums['sumSales']);
        $objPHPExcel->getActiveSheet()->getStyleByColumnAndRow($xOffset, $yOffset)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($xOffset, $yOffset++, $sums['sumUnsub']);
        $objPHPExcel->getActiveSheet()->getStyleByColumnAndRow($xOffset, $yOffset)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($xOffset, $yOffset, $sums['avgUnsubPerSends']);
        $objPHPExcel->getActiveSheet()->getStyleByColumnAndRow($xOffset, $yOffset++)->getNumberFormat()->applyFromArray(array('code' => PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE_00));
        $objPHPExcel->getActiveSheet()->getStyleByColumnAndRow($xOffset, $yOffset)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($xOffset, $yOffset  , $sums['avgUnsubPerOpens']);
        $objPHPExcel->getActiveSheet()->getStyleByColumnAndRow($xOffset, $yOffset)->getNumberFormat()->applyFromArray(array('code' => PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE_00));
    }

    /**
     * prepare data from autoresponders for output
     * @param $ardata
     * @return array
     */
    function prepareAutoresponderData($ardata) {

        $result = array();
        $formattedDataIncChange = array();

        foreach ($ardata as $autoresponder) {
            //here we load the autoresponder data with the order from the header column (ardata is table header line)
            $autoresponderData = $this->mcreports_model->loadAutoresponderData($autoresponder['mc_id']);

            //format data for autoresponders
            $formattedData = $this->calculateKPIsFromAutoresponderData($autoresponderData);

            //calculate change for each autoresponder from the previous week
            $changeData = $this->calculateChangeFromPreviousWeekFromAutoresponderData($formattedData);

            //merge ar formatted data with change previous week data
            $formattedDataIncChange = array_merge_recursive($formattedData, $changeData);

            if ($formattedDataIncChange != null) {
                array_push($result, $formattedDataIncChange);
            }
        }
        return $result;
    }

    /**
     * write calendar week header lines after each "row" of autoresponders
     * @param $xOffset of current position
     * @param $yOffset of current position
     * @param $objPHPExcel excel sheet object
     * @param $metadata
     */
    function writeCalendarWeekBreakerLine ($xOffset, $yOffset, $objPHPExcel, $metadata) {
        $headerLineColor = '00FF00';
        //write calendar_week breaker line
        $endIndex = ($metadata['sum_columns'] * 2 ) + 1; //+1 is sums column
        for ($i=0; $i <= $endIndex; $i++) { //+1 for sum column
            $objPHPExcel->getActiveSheet()->getStyleByColumnAndRow($xOffset+$i, $yOffset)->getFill()->applyFromArray(array('type' => PHPExcel_Style_Fill::FILL_SOLID,'startcolor' => array('rgb' => $headerLineColor)));
        }
    }

    /**
     * calculate change for each autoresponder from the previous week and calculate percentage
     * @param $autoresponderData
     * @return array
     */
    function calculateChangeFromPreviousWeekFromAutoresponderData($autoresponderData) {
        $keysForAutoresponder = $keysForTable = array_keys($autoresponderData);

        //delete first key because we can't output CHANGE data for the first week of an autoresponder running
        array_shift($keysForTable);

        $resultAR = array();

        foreach ($keysForTable as $tableData) {

            $keysAutoresponder_week =  array_search($tableData, $keysForAutoresponder);
            $keysAutoresponder_PreviousWeek = $keysAutoresponder_week - 1;

            //copy associative arrays to index arrays
            $autoresponderDataIndexed = array_values($autoresponderData);

            //calculate values for current autoresponder
            $changeSends = $this->calculatePercentage($autoresponderDataIndexed[$keysAutoresponder_week]['Sends'], $autoresponderDataIndexed[$keysAutoresponder_PreviousWeek]['Sends']);
            $changeOpens = $this->calculatePercentage($autoresponderDataIndexed[$keysAutoresponder_week]['Opens'], $autoresponderDataIndexed[$keysAutoresponder_PreviousWeek]['Opens']);
            $changeOpenrate = $this->calculatePercentage($autoresponderDataIndexed[$keysAutoresponder_week]['Openrate'], $autoresponderDataIndexed[$keysAutoresponder_PreviousWeek]['Openrate']);
            $changeKlicks = $this->calculatePercentage($autoresponderDataIndexed[$keysAutoresponder_week]['Klicks'], $autoresponderDataIndexed[$keysAutoresponder_PreviousWeek]['Klicks']);
            $changeKlickrate = $this->calculatePercentage($autoresponderDataIndexed[$keysAutoresponder_week]['Klickrate'], $autoresponderDataIndexed[$keysAutoresponder_PreviousWeek]['Klickrate']);
            $changeSales = 0;
            $changeUnsubscribed = $this->calculatePercentage($autoresponderDataIndexed[$keysAutoresponder_week]['Unsubscribed'], $autoresponderDataIndexed[$keysAutoresponder_PreviousWeek]['Unsubscribed']);
            $changeUnsubSends = $this->calculatePercentage($autoresponderDataIndexed[$keysAutoresponder_week]['Unsub/Sends'], $autoresponderDataIndexed[$keysAutoresponder_PreviousWeek]['Unsub/Sends']);
            $changeUnsubOpens = $this->calculatePercentage($autoresponderDataIndexed[$keysAutoresponder_week]['Unsub/Opens'], $autoresponderDataIndexed[$keysAutoresponder_PreviousWeek]['Unsub/Opens']);
            //

            //create data array
            $resultSingleAR = array (
                //$tableData =>
                //array(
                'changeSends' => $changeSends,
                'changeOpens' => $changeOpens,
                'changeOpenrate' => $changeOpenrate,
                'changeKlicks' => $changeKlicks,
                'changeKlickrate' => $changeKlickrate,
                'changeSales' => $changeSales,
                'changeUnsubscribed' => $changeUnsubscribed,
                'changeUnsubSends' => $changeUnsubSends,
                'changeUnsubOpens' => $changeUnsubOpens
                //)
            );

            $resultAR[$tableData] = $resultSingleAR;
        }

        return $resultAR;
    }

    /**
     * calculate the kpi which will be reported to the excel sheet for each mailchimp autoresponder
     * @param $autoresponderData on a specific autoresponder for all calendar weeks of database
     * @return array containing calculated values for all stored weeks
     */
    function calculateKPIsFromAutoresponderData($autoresponderData) {

        //get keys from autoresponder data
        $keysForTable = array_keys($autoresponderData['autoresponderData']);
        $keysForAutoresponder = array_keys($autoresponderData['autoresponderData']);

        //delete first key because we can't output data for the first week of an autoresponder running
        $firstKwIdentifier = array_shift($keysForTable);

        $resultAR = array();

        //we traverse all calendar weeks which have sufficient data and must to be filled for table output
        foreach ($keysForTable as $tableData) {

            $keysAutoresponder_week =  array_search($tableData, $keysForAutoresponder);
            $keysAutoresponder_PreviousWeek = $keysAutoresponder_week - 1;

            //copy associative arrays to index arrays
            $autoresponderDataIndexed = array_values($autoresponderData['autoresponderData']);

            //calculate values for current autoresponder
            $Sends = $autoresponderDataIndexed[$keysAutoresponder_week]['emails_sent'] - $autoresponderDataIndexed[$keysAutoresponder_PreviousWeek]['emails_sent'];
            $Opens = $autoresponderDataIndexed[$keysAutoresponder_week]['summary-opens'] - $autoresponderDataIndexed[$keysAutoresponder_PreviousWeek]['summary-opens'];
            $Openrate = $this->calculateRate($Sends, $Opens);
            $Klicks = $autoresponderDataIndexed[$keysAutoresponder_week]['summary-clicks'] - $autoresponderDataIndexed[$keysAutoresponder_PreviousWeek]['summary-clicks'];
            $Klickrate = $this->calculateRate($Sends, $Klicks);
            $Sales = 0;
            $Unsubscribed = $autoresponderDataIndexed[$keysAutoresponder_week]['summary-unsubscribes'] - $autoresponderDataIndexed[$keysAutoresponder_PreviousWeek]['summary-unsubscribes'];
            $UnsubSends = $this->calculateRate($Sends ,$Unsubscribed);
            $UnsubOpens = $this->calculateRate($Opens, $Unsubscribed);


            //create data array
            $resultSingleAR = array (
                //$tableData =>
                //array(
                    'Sends' => $Sends,
                    'Opens' => $Opens,
                    'Openrate' => $Openrate,
                    'Klicks' => $Klicks,
                    'Klickrate' => $Klickrate,
                    'Sales' => $Sales,
                    'Unsubscribed' => $Unsubscribed,
                    'Unsub/Sends' => $UnsubSends,
                    'Unsub/Opens' => $UnsubOpens
                //)
            );

            $resultAR[$tableData] = $resultSingleAR;
            }

        //if we have NO data for current autoresponder, we have to build an empty entry to maintain table layout
        if (empty($resultAR)) {
            //create data array
            $resultSingleAR = array (
                //$tableData =>
                //array(
                'Sends' => "",
                'Opens' => "",
                'Openrate' => "",
                'Klicks' => "",
                'Klickrate' => "",
                'Sales' => "",
                'Unsubscribed' => "",
                'Unsub/Sends' => "",
                'Unsub/Opens' => ""
                //)
            );

            $resultAR[$firstKwIdentifier] = $resultSingleAR;
        }

        return $resultAR;
    }

    /**
     * calculate rates
     * @param $Sends
     * @param $OpensOrKlicks
     * @return float|int|string
     */
    function calculateRate($Sends, $OpensOrKlicks) {
        if ($Sends > 0 && $OpensOrKlicks >= 0) {
            return (1/$Sends * $OpensOrKlicks);
        } elseif ($Sends <= 0 && $OpensOrKlicks > 0) {
            return "∞";
        } else { //we have no opens or klicks
            return 0;
        }
    }

    /**
     * calculate percentage of given values
     * @param $a
     * @param $b
     * @return int
     */
    function calculatePercentage($a, $b) {
        if ((is_numeric($a) && $a != 0) && (is_numeric($b) && $b != 0)) {
            $val = (1 - ($a / $b)) * -1;
            return $val;
        }
        return 0;
    }


    /**
     * set meta data for excel sheet and return initialized object
     * @return PHPExcel
     */
    function createExcelSheet() {
        $objPHPExcel = new PHPExcel();

        //get data from config
        $creator = $this->config->item('Excel_creator');
        $lastModifiedBy = $this->config->item('Excel_lastmodifiedBy');
        $title = $this->config->item('Excel_title');
        $subject = $this->config->item('Excel_subject');
        $description = $this->config->item('Excel_description');
        $keywords = $this->config->item('Excel_keywords');
        $category = $this->config->item('Excel_category');

        // Set properties
        $objPHPExcel->getProperties()->setCreator($creator)
            ->setLastModifiedBy($lastModifiedBy)
            ->setTitle($title)
            ->setSubject($subject)
            ->setDescription($description)
            ->setKeywords($keywords)
            ->setCategory($category);
        $objPHPExcel->getActiveSheet()->setTitle($title);
        //set fixed column and row
        $objPHPExcel->getActiveSheet()->freezePane('B4');

        return $objPHPExcel;
    }

    /**
     * write excel file to disk
     * @param $objPHPExcel
     * @param $ExcelFilename
     */
    function writeExcelFile($objPHPExcel, $ExcelFilename) {
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        // If you want to output e.g. a PDF file, simply do:
        //$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'PDF');
        $objWriter->save($ExcelFilename);
        chmod($ExcelFilename, 0777);
    }

    /**
     * redirect to excel download
     * @param $ExcelFilename for download
     */
    function downloadExcelFile($ExcelFilename) {
        //initiate excel download
        header('Content-type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="export.xlsx"');
        readfile($ExcelFilename);
    }

    /**
     * function for development only - for population of a fresh database with demo data
     * function is meant to be called from browser or cli with wget/curl
     */
    public function updateDbWithTestdata() {

        $this->load->model('mcreports_model');

        $datetime = date('Y-m-d H:i:s') ;
        $testData = array(
            'mc_id' => 'kaeJai0pho',
            'timestamp' => $datetime,
            'calendar_week' => 30,
            'year' => 2014,
            'web_id' => 587121,
            'list_id' => '84a25f3325',
            'title' => 'extremely popular autoresponder',
            'type' => 'regular',
            'status' => 'sent',
            'create_time' => '2014-04-17 08:54:41',
            'send_time' => '2014-04-17 15:08:22',
            'emails_sent' => 890,
            'summary-opens' => 663,
            'summary-clicks' => 19,
            'summary-unsubscribes' => 31
        );

        $result = $this->mcreports_model->storeDataInDatabase($testData);
    }

    /*
     * helper function
     */
    private function objectToArray($d) {
        if (is_object($d)) {
            // Gets the properties of the given object
            // with get_object_vars function
            $d = get_object_vars($d);
        }

        if (is_array($d)) {
            /*
            * Return array converted to object
            * Using __FUNCTION__ (Magic constant)
            * for recursive call
            */
            return array_map(array($this, __FUNCTION__), $d);
        }
        else {
            // Return array
            return $d;
        }
    }
}

/* End of file mcreports.php */
/* Location: ./application/controllers/mcreports.php */