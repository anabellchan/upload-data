<?php
require_once('..\upload-data\app\models\TriumfRow.php');

class UploadController extends \BaseController {

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
//        echo "<p>Temporary filename: $tempFilename</p>";
//        echo "<p>Temporary path: $inputFile</p>";

        echo "<h1>Filename: $filename</h1>";

        // validate file
        $fileExtension = strtolower(Input::file('file')->guessClientExtension());

        if (!self::isOfValidFileExtension($fileExtension)) {
            $message =  "<b>$filename is invalid.  Only accepts the following file extensions: " . self::acceptedExtensions() . "</b>";
            return View::make("home.error")->with('message', $message);
        }
        else {
            try {
                return $this->read($inputFile, $fileExtension, $category);
            } catch (Exception $e) {
                return View::make("home.error")->with('message', $e->getMessage());
            }
        }
    }

    public function read($inputFile, $fileExtension, $category) {
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
            throw new Exception('Error loading file: '.$e->getMessage());
//            die('Error loading file: '.$e->getMessage());
        }

        /*
         * Validation follows for:
         *   1. Header
         *   2. Item Model
         *   3. DB Insert
         */
        $objWorksheet = $objPHPExcel->getActiveSheet();
        $rows =  $objWorksheet->toarray();
        /* Validate header */
        //dd($rows);


        $itemColumns = Schema::getColumnListing('items');   //this gets array of all column headings for ITEMS
        array_push($itemColumns, 'item_name');   //add item_name to required column - will be used for kind table
        return $itemColumns;

        $message = $this->validateHeader($rows[0], $itemColumns);
//        $message = $this->validateHeader($objWorksheet->toarray()[HEADER_INDEX]);

        if ($message) {
            return $message;         // display list of incorrect header
        }
        return 'perfect - all headers valid';

        /* Validate against Item model's validation rules */
        /* Catherine here... */
        $categoryID = DB::table('categories')->where('name', $category)->first();
        if ( $categoryID == "" or $categoryID==null ) {
            throw new Exception('invalid category selected');
        }

        $validRows = array();
        $invalidRows = array();
        for($x = 1; $x < count($rows); $x++) {
            //return $rows[51];
            //$message .= $this->validateModel($row);



        }

        if ($message) {
            throw new Exception($message);         // display list of incorrect rows
        }

        /* Attempts to insert data to DB */
        try {
            $message += $this->importData($objWorksheet, $categoryID);
        }
        catch (Exception $e) {
            throw new Exception($e->getMessage());
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
        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0

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
            foreach($row as $columnHeader){
                self::$arrFields[$c] = $columnHeader;
                if($columnHeader == 'item_name') { $itemName = true; };

                $match = false;
                foreach($itemColumns as $iColumn){
                    if($columnHeader == $iColumn){
                        $match = true;
                        break;
                    }
                }
                if($match == false){
                    array_push($invalidHeaders, $columnHeader);  //this adds the invalid column header to array
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








    public function importData($worksheet, $categoryID) {
        $badRows = [];

        foreach ($worksheet->getRowIterator() as $row) {
            /*
             *    Referencial integrity on:
             *    1.  Catagory table : does not allow creation of new category if it doesn't exist.
             *    2.  Type table : creates a new type if it doesn't exist.
             */




            $item = new Item($row);
            // initialize item
            for ($c=0; $c>count(self::$arrFields); $c++) {
                $field = self::$arrFields[$c];
                $item->$field = $row[$c];
            }
            // invalid item, exit
            if (!$item->isValid()) {
                array_add($badRows, $row->getRowIndex(), 'Validation failed.');
                continue;
            }
            // kind exist, save item
            $kindID = DB::table('kinds')->where('id', $item->kind_id)->first();
            if ( $kindID == "" or $categoryID==null ) {
//                $tr = new TriumfRow($row);

            }
            else {
                $item->save();
            }

            // kind does not exist, create new kind
//            else {
//                try {
//                    DB::table('kinds')->insert(
//                        array('name' => , 'description' => $item->)
//                    );
//                } catch (Exception $e) {
//                    array_add($badRows, $row->getRowIndex(), $e->getMessage());
//                }

            }
        }
    public function insertRow() {
        try {
            return '';
        }
        catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function existInKind() {
        return '';
        $kind = new Kind;
        Kind::$kind->find($item->getRow()->kind_id);
    }

}
