<?php

class UploadController extends \BaseController {

    /*
     * Acceptable file extensions
     *
     * For now:  Excel5, CSV
     * Future support:  Excel2003XML, Excel2007, OOCalc, SYLK, Gnumeric
     *
     */
    public static $ACCEPTED_EXTENSIONS = array('xlsx' => 'Excel2007', 'csv' => 'CSV', 'xls' => 'CSV');

    public function acceptedExtensions() {
        $accepted_extensions = array_keys(self::$ACCEPTED_EXTENSIONS);
        return implode(', ' , $accepted_extensions);
    }

    public function isOfValidFileExtension($fileExtension) {
        return in_array($fileExtension, array_keys(self::$ACCEPTED_EXTENSIONS));
    }

    public function submit() {
        /*
        // debug purposes
        */
        $category = Input::get('categories');

        $filename = Input::file('file')->getClientOriginalName();
        $tempFilename = Input::file('file')->getFilename();
        $inputFile = Input::file('file')->getRealPath();

//        var_dump(Input::file('file'));
        echo "<h1>Filename: $filename</h1>";
        echo "<p>Temporary filename: $tempFilename</p>";
        echo "<p>Temporary path: $inputFile</p>";

        // validate file
        $fileExtension = strtolower(Input::file('file')->guessClientExtension());

        if (!self::isOfValidFileExtension($fileExtension)) {
            $error =  "<b>$filename is invalid.  Only accepts the following file extensions: " . self::acceptedExtensions() . "</b>";
            echo $error;
        }
        else {
            return $this->read($inputFile, $fileExtension, $category);
        }
    }

    public function read($inputFile, $fileExtension, $category) {
        //return $category;
        //return 'read action';
        error_reporting(E_ALL);
        ini_set('display_errors', TRUE);
        ini_set('display_startup_errors', TRUE);
        include '..\app\models\PHPExcel\IOFactory.php';

        echo "<hr>";
        $acceptedExtensions = self::$ACCEPTED_EXTENSIONS;
        $readerExtension = $acceptedExtensions[$fileExtension];
//        echo $readerExtension;

        /*
         * Load the file
         */
        try {
            $objReader = PHPExcel_IOFactory::createReader($readerExtension);
            $objReader->setReadDataOnly(true);
            $objPHPExcel = $objReader->load($inputFile);
        } catch (PHPExcel_Reader_Exception $e) {
            die('Error loading file: '.$e->getMessage());
        }

        /*
         * Validation follows for:
         *   1. Header
         *   2. Item Model
         *   3. DB Insert
         */
        $objWorksheet = $objPHPExcel->getActiveSheet();
        $rows =  $objWorksheet->toarray();
        //dd($rows);

        $message = '';

        /* Validate header */
        $message = $this->validateHeader($rows[0]);

        if ($message) {
            return $message;         // display list of incorrect header
        }
        //return 'perfect - all headers valid';

        /* Validate against Item model's validation rules */
        /* Catherine here... */
        if (DB::table('categories')->where('name', $category)->first() == "" ) {
            return 'invalid category selected';
        }

        $validRows = array();
        $invalidRows = array();
        for($x = 1; $x < count($rows); $x++) {
            //return $rows[51];
            //$message .= $this->validateModel($row);



        }

        if ($message) {
            return $message;          // display list of incorrect rows
        }

        /* Attempts to insert data to DB */
        foreach($rows as $row) {
            try {
                $message += importData($row);
            }
            catch (Exception $e) {
                $message += $e->getMessage();
                break;
            }
        }

        if ($message) {
            return $message;
        }

    }

    // Catherine here...
    public function validateModel($row) {
        $invalidRows = [];
        $validRows = [];
        $error_message = "";
        if(! Item::isValid($row)){
            array_push($invalidRows, $row);
            $error_message .= $row;
        } else {
            array_push($validRows, $row);
        }
        return $error_message;
    }



    public function writeTemplate() {
//        return 'tester';
        error_reporting(E_ALL);
        ini_set('include_path', ini_get('include_path').';../Classes/');
        include '..\upload-data\app\models\PHPExcel.php';
        include '..\upload-data\app\models\PHPExcel\Writer/Excel2007.php';

        // Create new PHPExcel object
        echo date('H:i:s') . " Create new PHPExcel object\n";
        $objPHPExcel = new PHPExcel();

        // Set properties
        echo date('H:i:s') . " Set properties\n";
        $objPHPExcel->getProperties()->setCreator("Maarten Balliauw");
        $objPHPExcel->getProperties()->setLastModifiedBy("Maarten Balliauw");
        $objPHPExcel->getProperties()->setTitle("Office 2007 XLSX Test Document");
        $objPHPExcel->getProperties()->setSubject("Office 2007 XLSX Test Document");
        $objPHPExcel->getProperties()->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.");


        // Add some data
        echo date('H:i:s') . " Add some data\n";
        $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->SetCellValue('A1', 'Hello');
        $objPHPExcel->getActiveSheet()->SetCellValue('B2', 'world!');
        $objPHPExcel->getActiveSheet()->SetCellValue('C1', 'Hello');
        $objPHPExcel->getActiveSheet()->SetCellValue('D2', 'world!');

        // Rename sheet
        echo date('H:i:s') . " Rename sheet\n";
        $objPHPExcel->getActiveSheet()->setTitle('Simple');


        // Save Excel 2007 file
        echo date('H:i:s') . " Write to Excel2007 format\n";
        $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
        $objWriter->save(str_replace('.php', '.xlsx', __FILE__));

        // Echo done
        echo date('H:i:s') . " Done writing file.\r\n";
    }



    public function validateHeader($row)
    {
        $invalidHeaders = array();   //if excel column header doesn't match ITEM column - push to this array

        try {
            $itemColumns = Schema::getColumnListing('items');   //this gets array of all column headings for ITEMS
            array_push($itemColumns, 'item_name');   //add item_name to required column - will be used for kind table
            //return $itemColumns;
            $itemName = false;
            //go through top header row of excel data compare headers to ITEM columns
            foreach($row as $columnHeaders){
                if($columnHeaders == 'item_name') { $itemName = true; };

                $match = false;
                foreach($itemColumns as $iColumn){
                    if($columnHeaders == $iColumn){
                        $match = true;
                        break;
                    }
                }
                if($match == false){
                    array_push($invalidHeaders, $columnHeaders);  //this adds the invalid column header to array
                }
            }
        }
        catch(Exception $e) {
            return $e->getMessage();
        }
        //return count($invalidHeaders);

        if(count($invalidHeaders) > 0 || $itemName == false){
            //if any of the excel headers are invalid return view with list of invalid headers and list of possible correct options
            return View::make("home.invalidheading")->with('allItems', array('invalidHeaders' => $invalidHeaders, 'validHeaders' => $itemColumns, 'itemName' => $itemName));
        }

        //return '<p>no invalid headers</p>';  //now it should iterate through all excel rows and list valid & invalid for confirmation

    }








    public function importData($row) {
        $item = new Item;

        /*
         * Referencial integrity on:
         *    1.  Catagory table
         *    2.  Type table
         */


    }
}
