<?php

class UploadController extends \BaseController {

    /*
     * Acceptable file extensions
     *
     * For now:  Excel5, CSV
     * Future support:  Excel2003XML, Excel2007, OOCalc, SYLK, Gnumeric
     *
     */
    const ACCEPTED_EXTENSIONS = array('xlsx' => 'Excel2007', 'csv' => 'CSV', 'xls' => 'CSV');

	/**
	 * Display main page.
	 *
	 * @return view - main page
	 */
//	public function index()
//	{
//        /** PHPExcel_IOFactory */
//        return View::make("upload");
//	}

    public function acceptedExtensions() {
        $accepted_extensions = array_keys(self::ACCEPTED_EXTENSIONS);
        return implode(', ' , $accepted_extensions);
    }
    public function isOfValidFileExtension($fileExtension) {
        return in_array($fileExtension, array_keys(self::ACCEPTED_EXTENSIONS));
    }

    public function read($inputFile, $fileExtension) {
        error_reporting(E_ALL);
        ini_set('display_errors', TRUE);
        ini_set('display_startup_errors', TRUE);
        include 'C:\phpprojects\upload-data\app\models\PHPExcel\IOFactory.php';

        echo "<hr>";
        $acceptedExtensions = self::ACCEPTED_EXTENSIONS;
        $readerExtension = $acceptedExtensions[$fileExtension];
//        echo $readerExtension;
        try {
            $objReader = PHPExcel_IOFactory::createReader($readerExtension);
            $objReader->setReadDataOnly(true);
            $objPHPExcel = $objReader->load($inputFile);
        } catch (PHPExcel_Reader_Exception $e) {
            die('Error loading file: '.$e->getMessage());
        }

        $objWorksheet = $objPHPExcel->getActiveSheet();
        //$s =  $objWorksheet->toarray()[0];
        $arr = $this->validateHeader($objWorksheet->toarray()[0]);
        return $arr ;

//        echo '<table>' . "\n";
//        foreach ($objWorksheet->getRowIterator() as $row) {
//            echo '<tr>' . "\n";
//
//            $cellIterator = $row->getCellIterator();
//            $cellIterator->setIterateOnlyExistingCells(false); // This loops all cells,
//            // even if it is not set.
//            // By default, only cells
//            // that are set will be
//            // iterated.
//            foreach ($cellIterator as $cell) {
//                echo '<td>' . $cell->getValue() . '</td>' . "\n";
//            }
//
//            echo '</tr>' . "\n";
//        }
//        echo '</table>' . "\n";

//        dd($objPHPExcel->getActiveSheet()->getCel(0)->getValue());

        //dd($objWorksheet);
//        foreach ($objWorksheet->getRowIterator() as $row) {
//            $cellIterator = $row->getCellIterator();
//            dd($cellIterator);
//            //return $row;
//        }

//        return $objWorksheet->getRowIterator();
//        $headers = $objWorksheet->getRowIterator()[0];
//        validateHeader($headers);
    }

    public function submit() {

        /*
        // debug purposes
        */
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
            return $this->read($inputFile, $fileExtension);
        }
    }

    public function validateHeader($row)
    {

        //return $row[1];
        //return 'test2';
//        $spreadsheetArray = array();   //this should be filled with data from EXCEL READER
//        $spreadsheetArray[0] = array("test", "test2", "id", "po_number"); //test results

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

        return 'no invalid headers';  //now it should iterate through all excel rows and list valid & invalid for confirmation

    }

    /**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function create()
	{
		//
	}


	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store()
	{
		//
	}


	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show($id)
	{
		//
	}


	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function edit($id)
	{
		//
	}


	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($id)
	{
		//
	}


	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id)
	{
		//
	}


}
