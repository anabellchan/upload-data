<?php


class HomeController extends \BaseController
{

    /*
     * Acceptable file extensions
     *
     * For now:  CSV
     *
     */
    const HEADER_INDEX = 0;
    public static $arrFields = [];
    public static $categoryID;
    public static $ACCEPTED_EXTENSIONS = array('xlsx' => 'Excel2007', 'csv' => 'CSV', 'xls' => 'CSV');

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
        return "Success";
    }

    public function read($inputFile, $fileExtension, $category)
    {
        /*
        * Initialization
        */
        error_reporting(E_ALL);
        ini_set('display_errors', TRUE);
        ini_set('display_startup_errors', TRUE);

        /*
         * Load the file
         */
        $acceptedExtensions = self::$ACCEPTED_EXTENSIONS;
        $readerExtension = $acceptedExtensions[$fileExtension];          // assign the correct reader for this file
        try {
            echo "<p>File loading.</p>";
            $objReader = PHPExcel_IOFactory::createReader($readerExtension);
            $objReader->setReadDataOnly(true);
            $objPHPExcel = $objReader->load($inputFile);
        } catch (PHPExcel_Reader_Exception $e) {
            throw new Exception('Error loading file: ' . $e->getMessage());
        }


        /*
         * Get active sheet
         */
        try {
            echo "<p>Spreadsheet loading.</p>";
            $objWorksheet = $objPHPExcel->getActiveSheet();
            $rows = $objWorksheet->toarray();
            $rows[0] = array_map('strtolower', $rows[0]);
        } catch (Exception $e) {
            throw new Exception('Error loading worksheet: ' . $e->getMessage());
        }

        /*
        *  Validate header
        */
        echo "<p>Validating header.</p>";

        $itemColumns = $this->getClientItemHeaders();
        //return $itemColumns;

        $message = $this->validateHeader($rows[0], $itemColumns);
        if ($message) {
            return $message;         // display list of incorrect header
        }
//        return 'perfect - all headers valid';


        /*
        *  Validate Category
        */
        try {
            $this->validateCategory($category);
        } catch (Exception $e) {
            throw new Exception('read validate category: ' . $e->getMessage());
        }

        /*
         *  Validate Rows
         */
        $message =  $this->validateRows($objWorksheet);
        if ($message) {
            return $message;         // display list of incorrect header
        }

        /*
        *  Insert data to DB
        */
        try {
            echo "<p>Importing data.</p>";
            return $this->importData($objWorksheet, $itemColumns);
        } catch (Exception $e) {
            throw new Exception('read insert row: ' . $e->getMessage());
        }
    }

    /*
     *  validateCategory
     *  throws: invalid category error
     */
    public function validateCategory($category) {
        echo "<p>Validating item's category.</p>";
        self::$categoryID = DB::table('categories')->where('name', $category)->first()->id;
        if (self::$categoryID == "" or self::$categoryID == null) {
            //return 'invalid category!';
            throw new Exception('invalid category selected');
        }
        echo "Category validated.";
    }

    /*
     *   validateRows
     *   - This method takes a multi-dimensional array as an argument
     *   - returns: $invalidRows
     */
    public function validateRows($excelSheet)
    {
        echo "<p>Validate item_name column.</p>";
        $invalidRows = [];
        $itemNameExists = false;
        $headers = $excelSheet->toarray()[0];
//        if ( ! is_array($excelSheet[0]) ) {
//            return 'First row excelSheet is not an array. ';
//        }
        $position = array_search('item_name', $headers);
        $numOfRows = $excelSheet->getHighestDataRow();

        for ($i = 2; $i <= $numOfRows; $i++){  // data starts in row 1
            $value = $excelSheet->getCellByColumnAndRow($position, $i)->getValue();

            if ($value == null || $value == ""){
                $rowNum = $i;
                $r = "Row: " . $rowNum;
                array_push($invalidRows, $r);
            }
        }

        if (count($invalidRows) > 0) {
            return View::make("home.invalidrows")->with('allItems',
                array('validation' => 'Please specify "item_name" on the following:', 'invalidRows' => $invalidRows));
        }
    }


    public function exportCategory() {
        $category = Input::get('categories');

        $categoryID = DB::table('categories')->where('name', $category)->first()->id;
        $items = DB::table('items')->where('category_id', $categoryID)->get();

        // save kind IDs
        $kinds = [];
        foreach($items as $item) {
            $items[$item->kind_id] = '';
        }
        // save kind names
        foreach ($kinds as $key => $value) {
            $kinds[$key] = DB::table('kinds')->where('id', $key)->first()->name;
        }

        // change kind_id to kind's name
        foreach($items as $item) {

        }



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
//        for anabell

        //set row cell styles
        $headerCells = 'A1:' . $letter . "1";
        $objPHPExcel->setActiveSheetIndex(0)->getStyle($headerCells)->getFont()->setBold(true);
        $objPHPExcel->setActiveSheetIndex(0)->getRowDimension('1')->setRowHeight(20);


        // Rename worksheet
        $objPHPExcel->getActiveSheet()->setTitle($category);

        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $objPHPExcel->setActiveSheetIndex(0);

        // Redirect output to a client’s web browser (Excel5)
        $filename = "Triumf_" . $category . "_Inventory.xls";
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename=' . $filename);
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

    /*
     *  writeTemplate
     *  - creates a template file
     */
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
//        for anabell

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

    /*
     *   validateHeader
     *   - on success, exit
     *   - on errors, loads View to show invalid headers
     *   - $itemColumns - list of valid column names
     *   - $row - list of input column names
     */
    public function validateHeader($row, $itemColumns)
    {
        $invalidHeaders = array();   //if excel column header doesn't match ITEM column - push to this array

        try {

            $itemName = false;
            //go through top header row of excel data compare headers to ITEM columns
            $c = 0;
            foreach ($row as $columnHeader) {
//                $columnHeader = strtolower($columnHeader);
                self::$arrFields[$c] = $columnHeader;
                if ($columnHeader == 'item_name') {
                    $itemName = true;
                };

                $match = false;
                foreach ($itemColumns as $iColumn) {
//                    $iColumn = strtolower($iColumn);
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


    /*
     *   importData
     *   - inserts data to DB
     *   - validates type name
     *   - throws error for invalid DB transactions
     *   - $worksheet - data to be imported
     *   - $itemColumns - list of valid column names
     */
    public function importData($worksheet, $itemColumns)
    {
        // normalize input column names
        $headers = $worksheet->toarray()[0];
        $headers = array_map('strtolower', $headers);
        /*
         *    Check referencial integrity on:
         *    1.  Catagory table : does not allow creation of new category if it doesn't exist.
         *    2.  Kind table : creates a new kind if it doesn't exist.
         */
        $badRows = [];
        $numOfRows = $worksheet->getHighestDataRow();


        // initialize item
        // column 0 is 'A', 27 is 'AB'
        // row 2 - start of data, row 1 is column names
        for ($row = 2; $row <= $numOfRows; $row++) {
            $item = new Item();
            $item->category_id = intval(self::$categoryID);
            $length = count($headers);
            for ($col = 0; $col < $length; $col++) {
                $value = $worksheet->getCellByColumnAndRow($col, $row)->getValue();
//                echo "here: " . $row . $col . '- ' . $value;
                $field = $headers[$col];
                $item->$field = $value;
            }


            /*
             *   Validate Kind - if kind do not exists, create a new kind.
             */
            try {
                echo "<p>Validating item's kind.</p>";
                $this->addKind($item);
            } catch (Exception $e) {
                throw new Exception('Type validation failed - Row: ' . $row);
            }

            /*
             *   Validate against model - validate item against the validation rules of Item
             */
            echo "<p>Validating against model's validation rule.</p>";
            if (!$item->isValid()) {
                array_push($badRows, 'Model failed - Row: ' . $row);
            }

            /*
             *   Insert Item to DB - save item!
             */
            try {
                unset($item->item_name);
                $item->save();
            } catch (Exception $e) {
                throw new Exception('read - insert Item: ' . $e->getMessage());
            }
        }
//        echo count($badRows);
        if (count($badRows) > 0) {
            return View::make("home.invalidrows")->with('allItems', array('invalidRows' => $badRows, 'validation' => 'Model validation failed: '));
        }

        return View::make("home.invalidrows")->with('allItems', array('invalidRows' => $badRows, 'validation' => 'Success'));

    }

    /*
     *  addKind
     *    - If kind do not exist, create a new one
     *    - If kind already exists, do nothing
     */
    public function addKind($item)
    {
        $name = $item->item_name;
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
    }

    /*
     *   getClientItemHeaders
     *
     */
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
