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
        $inputFile = Input::file('file')->getRealPath();

        echo "<h1>Filename: $filename</h1>";
        echo "<hr>";


        // validate file
        $fileExtension = strtolower(Input::file('file')->guessClientExtension());

        if (!self::isOfValidFileExtension($fileExtension)) {
            $message = "<p>$filename is invalid.  Only accepts the following file extensions: " . self::acceptedExtensions() . "</p>";
            return View::make("home.error")->with('message', $message);
        } else {
            echo "<p>File extension valid.</p>";
            try {
                return $this->read($inputFile, $fileExtension, $category);
            } catch (Exception $e) {
                return View::make("home.error")->with('message', 'submit: ' . $e->getMessage());
            }
        }
    }

    public function read($inputFile, $fileExtension, $category)
    {
        /*
        * Initialization
        */
        error_reporting(E_ALL);
        ini_set('display_errors', TRUE);
        ini_set('display_startup_errors', TRUE);
        include '..\upload-data\app\models\PHPExcel\IOFactory.php';



        /*
         * Load the file
         */

        // assign the correct reader for this file
        $acceptedExtensions = self::$ACCEPTED_EXTENSIONS;
        $readerExtension = $acceptedExtensions[$fileExtension];
        try {
            echo "<p>File loading.</p>";
            $objReader = PHPExcel_IOFactory::createReader($readerExtension);
            $objReader->setReadDataOnly(true);
            $objPHPExcel = $objReader->load($inputFile);
        } catch (PHPExcel_Reader_Exception $e) {
            throw new Exception('Error loading file: ' . $e->getMessage());
        }

        /*
         * Validation follows for:
         *   1. Header
         *   2. Category
         *   3. Item Model
         *   4. DB Insert
         */
        try {
            echo "<p>Spreadsheet loading.</p>";
            $objWorksheet = $objPHPExcel->getActiveSheet();
            $rows = $objWorksheet->toarray();
        } catch (Exception $e) {
            throw new Exception('Error loading worksheet: ' . $e->getMessage());
        }

        /*
        *  Validate header
        */
        //dd($rows);

        echo "<p>Validating header.</p>";
        $itemColumns = Schema::getColumnListing('items');   //this gets array of all column headings for ITEMS
        array_push($itemColumns, 'item_name');   //add item_name to required column - will be used for kind table
        //return $itemColumns;

        $message = $this->validateHeader($rows[0], $itemColumns);

//        if ($message) {
//            return $message;         // display list of incorrect header
//        }
//        return 'perfect - all headers valid';

        /*
        *  Validate Category
        *  Catherine here...
        */
        echo "<p>Validating item's category.</p>";
        $categoryID = DB::table('categories')->where('name', $category)->first()->id;
        if ($categoryID == "" or $categoryID == null) {
            throw new Exception('invalid category selected');
        }


        /*
        *  Attempts to insert data to DB
        */
        try {
            echo "<p>Importing data.</p>";
            return $this->importData($objWorksheet, $categoryID, $itemColumns);
        } catch (Exception $e) {
            throw new Exception('read: ' . $e->getMessage());
        }
    }

    // Catherine here...
    public function validateModel($row)
    {
        echo "<p>Validate model.</p>";
        $invalidRows = [];
        $validRows = [];
        $error_message = "";
        if (!Item::isValid($row)) {
            array_push($invalidRows, $row);
            $error_message .= $row;
        } else {
            array_push($validRows, $row);
        }
        return $error_message;
    }

    public function writeTemplate()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', TRUE);
        ini_set('display_startup_errors', TRUE);
        date_default_timezone_set('America/Los_Angeles');

        if (PHP_SAPI == 'cli')
            die('This example should only be run from a Web Browser');

        /** Include PHPExcel */
//        include '..\upload-data\app\models\PHPExcel.php';

// Create new PHPExcel object
        $objPHPExcel = new PHPExcel();

// Set document properties
        $objPHPExcel->getProperties()->setCreator("Maarten Balliauw")
            ->setLastModifiedBy("Maarten Balliauw")
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Test result file");


// Add some data
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', 'Hello')
            ->setCellValue('B2', 'world!')
            ->setCellValue('C1', 'Hello')
            ->setCellValue('D2', 'world!');

// Miscellaneous glyphs, UTF-8
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A4', 'Miscellaneous glyphs')
            ->setCellValue('A5', 'éàèùâêîôûëïüÿäöüç')
            ->setCellValue('A6', 'IT WORKS!!!');

// Rename worksheet
        $objPHPExcel->getActiveSheet()->setTitle('Simple');


// Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $objPHPExcel->setActiveSheetIndex(0);


// Redirect output to a client’s web browser (Excel5)
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="01simple.xls"');
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
            $item->category_id = $categoryID;
            $length = count($headers);
            for ($col = 0; $col < $length; $col++) {
                $value = $worksheet->getCellByColumnAndRow($col, $row)->getValue();
                $field = $headers[$col];
                $item->$field = $value;
                //echo '<p>' . $field . ': ' . $item->$field . '</p>';
            }


            // if kind do not exists, create a new kind
            try {
                echo "<p>Validating item's kind.</p>";
                $this->addKind($item);
            } catch (Exception $e) {
                array_add($badRows, $row, 'importData: ' . $e->getMessage());
            }

            // validate item against the validation rules of Item
            if (!$item->isValid()) {
                echo "<p>Validating against model's validation rule.</p>";
                array_add($badRows, $row, 'Validation failed.');
                continue;
            }
//            var_dump($item);

            // finally, safely arrives here, save item!
//            $item->save();

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
            echo "<p>Kind exists.</p>";
            $item->kind_id = $result->id;
        } else {
            try {
                $kind = new Kind;
                $kind->name = $name;
                $kind->save();
                echo "<p>Item's kind is new, adding " . $name . " to kinds table...</p>";
            } catch (Exception $e) {
                throw new Exception('addKind: ' . $e->getMessage());
            }
            $result = Kind::where('name', '=', $name)->first();
            $item->kind_id = $result->id;

        }
        unset($item->item_name);
        //var_dump($item);

    }
}
