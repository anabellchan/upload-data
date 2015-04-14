<?php
require_once('..\upload-data\app\models\TriumfRow.php');

class UploadController extends \BaseController
{

    /*
     * Acceptable file extensions
     *
     * For now:  Excel5, CSV
     * Future support:  Excel2003XML, Excel2007, OOCalc, SYLK, Gnumeric
     *
     */
    const HEADER_INDEX = 0;
    public static $arrFields = [];

    public static $ACCEPTED_EXTENSIONS = array('xlsx' => 'Excel2007', 'csv' => 'CSV', 'xls' => 'CSV');

//    public static $message = '';  // should have only one message variable


    public function acceptedExtensions()
    {
        $accepted_extensions = array_keys(self::$ACCEPTED_EXTENSIONS);
        return implode(', ', $accepted_extensions);
    }

    public function isOfValidFileExtension($fileExtension)
    {
        return in_array($fileExtension, array_keys(self::$ACCEPTED_EXTENSIONS));
    }

    public function submit()
    {
        /*
        // debug purposes
        */
        $category = Input::get('categories');

        $filename = Input::file('file')->getClientOriginalName();
        $tempFilename = Input::file('file')->getFilename();
        $inputFile = Input::file('file')->getRealPath();

//        var_dump(Input::file('file'));
//        echo "<p>Temporary filename: $tempFilename</p>";
//        echo "<p>Temporary path: $inputFile</p>";

        echo "<h1>Filename: $filename</h1>";

        // validate file
        $fileExtension = strtolower(Input::file('file')->guessClientExtension());

        if (!self::isOfValidFileExtension($fileExtension)) {
            $message = "<b>$filename is invalid.  Only accepts the following file extensions: " . self::acceptedExtensions() . "</b>";
            return View::make("home.error")->with('message', $message);
        } else {
            try {
                return $this->read($inputFile, $fileExtension, $category);
            } catch (Exception $e) {
                return View::make("home.error")->with('message', 'submit: ' . $e->getMessage());
            }
        }
    }

    public function read($inputFile, $fileExtension, $category)
    {
        //return $category;
        //return 'read action';
        error_reporting(E_ALL);
        ini_set('display_errors', TRUE);
        ini_set('display_startup_errors', TRUE);
        include '..\upload-data\app\models\PHPExcel\IOFactory.php';

        echo "<hr>";
        $acceptedExtensions = self::$ACCEPTED_EXTENSIONS;
        $readerExtension = $acceptedExtensions[$fileExtension];

        /*
         * Load the file
         */
        try {
            $objReader = PHPExcel_IOFactory::createReader($readerExtension);
            $objReader->setReadDataOnly(true);
            $objPHPExcel = $objReader->load($inputFile);
        } catch (PHPExcel_Reader_Exception $e) {
            throw new Exception('Error loading file: ' . $e->getMessage());
//            die('Error loading file: '.$e->getMessage());
        }

        /*
         * Validation follows for:
         *   1. Header
         *   2. Item Model
         *   3. DB Insert
         */
        $objWorksheet = $objPHPExcel->getActiveSheet();
        $rows = $objWorksheet->toarray();

        /* Validate header */
        //dd($rows);


        //$itemColumns = Schema::getColumnListing('items');   //this gets array of all column headings for ITEMS
        //array_push($itemColumns, 'item_name');   //add item_name to required column - will be used for kind table
        $itemColumns = $this->getClientItemHeaders();
        //return $itemColumns;

        $message = $this->validateHeader($rows[0], $itemColumns);

//        if ($message) {
//            return $message;         // display list of incorrect header
//        }
//        return 'perfect - all headers valid';

        /* Validate against Item model's validation rules */
        /* Catherine here... */
        $categoryID = DB::table('categories')->where('name', $category)->first()->id;
        if ($categoryID == "" or $categoryID == null) {
            throw new Exception('invalid category selected');
        }

//        $validRows = array();
//        $invalidRows = array();
//        for($x = 1; $x < count($rows); $x++) {
//            //return $rows[51];
//            //$message .= $this->validateModel($row);
//
//
//
//        }
//
//        if ($message) {
//            throw new Exception($message);         // display list of incorrect rows
//        }

        /* Attempts to insert data to DB */
        try {
            return $this->importData($objWorksheet, $categoryID, $itemColumns);
        } catch (Exception $e) {
            throw new Exception('read: ' . $e->getMessage());
        }
    }


    //This method takes a multi-dimensional array as an argument

    public function validateRows($excelSheet)
    {
        $invalidRows = [];
        $itemNameExists = false;
        $headers = $excelSheet->toarray()[0];
        if ( ! is_array($excelSheet[0]) ) {
            return 'First row excelSheet is not an array. ';
        }
        $position = array_search('item_name', $headers);
        $numOfRows = $excelSheet->getHighestDataRow();

        for ($i = 0; $i < $numOfRows; $i++){
            $value = $excelSheet->getCellByColumnAndRow($position, $i)->getValue();
            if ($value == null || $value == ""){
                array_push($invalidRows, $excelSheet[$i]);
            }
        }
        return $invalidRows;
    }


    public function writeTemplate()
    {
        $itemColumns = $this->getClientItemHeaders();

        error_reporting(E_ALL);
        ini_set('display_errors', TRUE);
        ini_set('display_startup_errors', TRUE);
        date_default_timezone_set('America/Los_Angeles');

        if (PHP_SAPI == 'cli')
            die('This example should only be run from a Web Browser');

        /** Include PHPExcel */
// include '..\upload-data\app\models\PHPExcel.php';

    // Create new PHPExcel object
        $objPHPExcel = new PHPExcel();

    // Set document properties
        $objPHPExcel->getProperties()->setCreator("")
            ->setLastModifiedBy("")
            ->setTitle("Triumf Inventory")
            ->setSubject("Triumf Inventory")
            ->setDescription("Triumf Inventory Spreadsheet.")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Triumf Inventory");


    // Add some data
//        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', 'ENTER INVENTORY CATEGORY NAME:');
//        $objPHPExcel->setActiveSheetIndex(0)->mergeCells('A1:H1');

    // Miscellaneous glyphs, UTF-8
        $letter = 'A';
        for($i = 0; $i < count($itemColumns); $i++) {
            $cell = $letter.'1';

            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cell , $itemColumns[$i]);
            $objPHPExcel->setActiveSheetIndex(0)->getColumnDimension($letter)->setAutoSize(true);
            $letter++;
        }

    //set row cell styles
        $headerCells = 'A1:' . $letter . "1";
        $objPHPExcel->setActiveSheetIndex(0)->getStyle($headerCells)->getFont()->setBold(true);
        $objPHPExcel->setActiveSheetIndex(0)->getRowDimension('1')->setRowHeight(20);


        // Rename worksheet
        $objPHPExcel->getActiveSheet()->setTitle('Triumf Inventory');

    // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $objPHPExcel->setActiveSheetIndex(0);

    // Redirect output to a client’s web browser (Excel5)
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="Triumf_Inventory_Spreadsheet.xls"');
        header('Cache-Control: max-age=0');
    // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');

    // If you're serving to IE over SSL, then the following may be needed
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

    public function validateHeader($row, $itemColumns)
    {
        $invalidHeaders = array();   //if excel column header doesn't match ITEM column - push to this array

        try {

            //return $itemColumns;
            $itemName = false;
            //go through top header row of excel data compare headers to ITEM columns
            $c = 0;
            foreach ($row as $columnHeader) {
                self::$arrFields[$c] = $columnHeader;
                if ($columnHeader == 'item_name') {
                    $itemName = true;
                };

                $match = false;
                foreach ($itemColumns as $iColumn) {
                    if ($columnHeader == $iColumn) {
                        $match = true;
                        break;
                    }
                }
                if ($match == false) {
                    array_push($invalidHeaders, $columnHeader);  //this adds the invalid column header to array
                }
            }
        } catch (Exception $e) {
            return 'validateHeader: ' . $e->getMessage();
        }
        //return count($invalidHeaders);

        if (count($invalidHeaders) > 0 || $itemName == false) {
            //if any of the excel headers are invalid return view with list of invalid headers and list of possible correct options
            return View::make("home.invalidheading")->with('allItems', array('invalidHeaders' => $invalidHeaders, 'validHeaders' => $itemColumns, 'itemName' => $itemName));
        }

        //return '<p>no invalid headers</p>';  //now it should iterate through all excel rows and list valid & invalid for confirmation

    }


    public function importData($worksheet, $categoryID, $itemColumns)
    {
        $headers = $worksheet->toarray()[0];

        /*
         *    Check referencial integrity on:
         *    1.  Catagory table : does not allow creation of new category if it doesn't exist.
         *    2.  Kind table : creates a new kind if it doesn't exist.
         */
        $badRows = [];
        $numOfRows = $worksheet->getHighestDataRow();
        $numOfColumns = $worksheet->getHighestDataColumn();
        $numOfColumnsIndex = PHPExcel_Cell::columnIndexFromString($numOfColumns);

        //echo '<p>' . $numOfRows . '</p>';
        //echo '<p>' . $numOfColumns . '</p>';
        //echo '<p>Column Index: ' . $numOfColumnsIndex . '</p>';

        // initialize item
        // column 0 is 'A', 27 is 'AB'
        // row 1 is '1'
        for ($row = 2; $row <= $numOfRows; $row++) {
            $item = new Item();
            // initialize item
            // ignore 'item_name' column, so only go up to count()-1
            // this is so we can validate against model Item, w/c doesn't expect 'item_name'
            $length = count($headers);
            for ($col = 0; $col < $length; $col++) {
                $value = $worksheet->getCellByColumnAndRow($col, $row)->getValue();
                $field = $headers[$col];
                $item->$field = $value;
                echo '<p>' . $field . ': ' . $item->$field . '</p>';
            }


            // if kind do not exists, create a new kind
            try {
                $this->addKind($item);
            } catch (Exception $e) {
                array_add($badRows, $row, 'importData: ' . $e->getMessage());
            }

            // validate item against the validation rules of Item
            if (!$item->isValid()) {
                array_add($badRows, $row->getRowIndex(), 'Validation failed.');
                continue;
            }

            // finally, safely arrives here, save item!
            $item->save();

        }
    }

    /*
     *  addKind
     *    1. If kind do not exist, create a new one
     *    2. If kind already exists, do nothing
     */
    public function addKind($item)
    {
        echo '<hr>';
        $name = intval($item->item_name);
        $result = Kind::where('name', '=', $name)->first();
        if ($result) {
            echo 'kind exist.';
            return '';
        } else {
            try {
                $kind = new Kind;
                $kind->name = $name;
                $kind->save();
                echo 'adding kind to db...';
            } catch (Exception $e) {
                throw new Exception('addKind: ' . $e->getMessage());
            }
        }
    }


    public function getClientItemHeaders()
    {
        $itemColumns = Schema::getColumnListing('items');   //this gets array of all column headings for ITEMS

        $key = array_search('id', $itemColumns);
        unset($itemColumns[$key]);

        $key = array_search('kind_id', $itemColumns);
        unset($itemColumns[$key]);

        $key = array_search('category_id', $itemColumns);
        unset($itemColumns[$key]);

        array_unshift($itemColumns, "item_name");

        return $itemColumns;
    }

}
