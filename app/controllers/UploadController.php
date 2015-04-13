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
                $this->read($inputFile, $fileExtension);
            } catch (Exception $e) {
                return View::make("home.error")->with('message', $e->getMessage());
            }
        }
    }

    public function read($inputFile, $fileExtension) {
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
        $message = $this->validateHeader($objWorksheet->toarray()[HEADER_INDEX]);

        if ($message) {
            return $message;         // display list of incorrect header
        }

//        /* Validate against Item model's validation rules */
//        /* Catherine here... */
//        foreach($rows as $row) {
//            $message += validateModel($row);
//        }
//
//        if ($message) {
//            return $message;          // display list of incorrect rows
//        }

//        /* Attempts to insert data to DB */
//        try {
//            $this->importData($objWorksheet);
//        } catch (Exception $e) {
//            throw new Exception($e->getMessage());
//        }

    }

    public function validateHeader($row)
    {
        $invalidHeaders = array();   //if excel column header doesn't match ITEM column - push to this array

        try {
            $itemColumns = Schema::getColumnListing('items');   //this gets array of all column headings for ITEMS
            //return $itemColumns;

            //go through top header row of excel data compare headers to ITEM columns
            foreach($row as $columnHeaders){
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

        if(count($invalidHeaders) > 0){
            //if any of the excel headers are invalid return view with list of invalid headers and list of possible correct options
            return View::make("home.invalidheading")->with('allItems', array('invalidHeaders' => $invalidHeaders, 'validHeaders' => $itemColumns));
        }

        return '<p>no invalid headers</p>';  //now it should iterate through all excel rows and list valid & invalid for confirmation

    }

    // Catherine here...
    public function validateModel($row) {
        return '';
    }

    public function importData($worksheet) {
        $badRows = [];

        foreach ($worksheet->getRowIterator() as $row) {
            /*
             *    Referencial integrity on:
             *    1.  Catagory table : does not allow creation of new category if it doesn't exist.
             *    2.  Type table : creates a new type if it doesn't exist.
             */

            $item = new Item($row);
            if (!$this->existInCategory($item->category_id)) {
                return 'Category $item->category_id does not exist yet.  Create a new one, or use an existing one.';
            }
            if ($this->existInKind() && $this->existInCategory()) {
                $item->save();
            }

            try {
                $this->insertRow($row);
            }
            catch (Exception $e) {
                array_add($badRows, $row->getRowIndex(), $e->getMessage());
//                $tr = new TriumfRow($row);
//                array_add($badRows, $tr->getRowNum(), $message);
//                $message += $e->getMessage();
                break;
            }

        }




    }
    public function insertRow() {
        return '';
        try {

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

    public function existInCategory() {
        $category = new Category;
        $id = $item->Row()->category_id;
        $result = Category::$category->find($categoryID);
        if (!result) {
            throw exception("Category $id does not exist.");
        }

    }
}
