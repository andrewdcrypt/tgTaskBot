<?php

class Gsheets {
    
    //development
    protected $service_account_file_path = __DIR__ . "/mFolder/tgkey/YOUR_GSHEET_KEY_PATH_HERE";
    protected $spreadSheetId = "YOUR_GSHEET_ID_HERE";

    //Production Keys
    // protected $service_account_file_path = __DIR__ . "/mFolder/tgkey/YOUR_GSHEET_KEY_PATH_HERE";
    // protected $spreadSheetId = "YOUR_GSHEET_ID_HERE";

    function __construct(){
    }

    /**
     * @param string $valueInputOption google option for how the input value will be updated/inserted into the sheet
     * @param array $headers
     * @return json
     */
    public function setSpreadSheet(string $valueInputOption, array $headers){
        try{  
            if (empty($this->spreadSheetId)){
                return json_encode([
                    'result' => 'failed',
                    'response' => 'Missing Spread Sheet ID',                    
                ]);
            }else if (empty($headers)){
                return json_encode([
                    'result' => 'failed',
                    'response' => 'Missing Headers',                    
                ]);
            }else if(empty($this->service_account_file_path)){
                return json_encode([
                    'result' => 'failed',
                    'response' => 'Missing Google Service Account File',                    
                ]);
            }else{
                putenv('GOOGLE_APPLICATION_CREDENTIALS='.$this->service_account_file_path);

                $client = new Google_Client();
                $client->useApplicationDefaultCredentials();
                $client->addScope(Google_Service_Sheets::SPREADSHEETS);
               
                $service = new Google_Service_Sheets($client);

                //Add headers to current sheet
                $body = new Google_Service_Sheets_ValueRange([
                    'values' => $headers,
                ]);

                $params = [
                    'valueInputOption' => $valueInputOption,
                    'insertDataOption' => "INSERT_ROWS"
                ];

                $service->spreadsheets_values->append($this->spreadSheetId, 'A1', $body, $params);

                $body = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
                    'requests' => [
                        'addSheet' => [
                            'properties' => [
                                'title' => 'Users'
                            ]
                        ],
                    ]
                ]);

                $service->spreadsheets->batchUpdate($this->spreadSheetId,$body);

                $body = new Google_Service_Sheets_ValueRange([
                    'values' => [["Chat ID", "User ID", "Username"]],
                ]);

                $service->spreadsheets_values->append($this->spreadSheetId, 'Users!A1', $body, $params);

                return json_encode([
                    'result' => 'success',
                    'response' => ''
                ]);
                
            }
        }catch(\Exception $e){
            file_put_contents('gsheet_error.txt','Gsheet Error: setSpreadSheet: '.PHP_EOL,FILE_APPEND);
            file_put_contents('gsheet_error.txt', $e->getMessage().PHP_EOL, FILE_APPEND);
            return json_encode([
                'result' => 'failed',
                'response' => $e->getMessage()
            ]);
        }
    }

    /**
     * @param string $range sheet range
     * @param string $valueInputOption google option for how the input value will be updated/inserted into the sheet
     * @param array $task
     * @return json
     */
    public function createTask(string $range, string $valueInputOption, array $task){
        try{
            if (empty($this->spreadSheetId)){
                return json_encode([
                    'result' => 'failed',
                    'response' => 'Missing Spread Sheet ID',                    
                ]);
            }
            else if (empty($task)){
                return json_encode([
                    'result' => 'failed',
                    'response' => 'Missing Task',                    
                ]);
            }else if(empty($this->service_account_file_path)){
                return json_encode([
                    'result' => 'failed',
                    'response' => 'Missing Google Service Account File',                    
                ]);
            }else{
                putenv('GOOGLE_APPLICATION_CREDENTIALS='.$this->service_account_file_path);
                $client = new Google_Client();
                $client->useApplicationDefaultCredentials();
                $client->addScope(Google_Service_Sheets::SPREADSHEETS);
               
                $service = new Google_Service_Sheets($client);
    
                $body = new Google_Service_Sheets_ValueRange([
                    'values' => [$task],
                ]);
    
                $params = [
                    'valueInputOption' => $valueInputOption,
                    'insertDataOption' => "INSERT_ROWS"
                ];
    
                $result = $service->spreadsheets_values->append($this->spreadSheetId, $range, $body, $params);
     
                return json_encode([
                    'result' => 'success',
                    'response' => $result->getUpdates()->getUpdatedCells()
                ]);
            }
        }catch(\Exception $e){
            file_put_contents('gsheet_error.txt','Gsheet Error: createTask: '.PHP_EOL,FILE_APPEND);
            file_put_contents('gsheet_error.txt', $e->getMessage().PHP_EOL, FILE_APPEND);
            return json_encode([
                'result' => 'failed',
                'response' => $e->getMessage()
            ]);
        }
    }

    /**
     * @param string $range sheet range
     * @param string $valueInputOption google option for how the input value will be updated/inserted into the sheet
     * @param array $description
     * @return json
     */
    public function addDescription(string $range, string $valueInputOption, array $description){
        try{
            if (empty($this->spreadSheetId)){
                return json_encode([
                    'result' => 'failed',
                    'response' => 'Missing Spread Sheet ID',                    
                ]);
            }
            else if (empty($description)){
                return json_encode([
                    'result' => 'failed',
                    'response' => 'Missing Description',                    
                ]);
            }else if(empty($this->service_account_file_path)){
                return json_encode([
                    'result' => 'failed',
                    'response' => 'Missing Google Service Account File',                    
                ]);
            }else{
                putenv('GOOGLE_APPLICATION_CREDENTIALS='.$this->service_account_file_path);
                $client = new Google_Client();
                $client->useApplicationDefaultCredentials();
                $client->addScope(Google_Service_Sheets::SPREADSHEETS);
               
                $service = new Google_Service_Sheets($client);
    
                $body = new Google_Service_Sheets_ValueRange([
                    'values' => [$description],
                ]);
    
                $params = [
                    'valueInputOption' => $valueInputOption,
                ];
    
                $result = $service->spreadsheets_values->update($this->spreadSheetId, $range, $body, $params);
     
                return json_encode([
                    'result' => 'success',
                    'response' => $result->getUpdatedCells()
                ]);
            }
        }catch(\Exception $e){
            file_put_contents('gsheet_error.txt','Gsheet Error: addDescription: '.PHP_EOL,FILE_APPEND);
            file_put_contents('gsheet_error.txt', $e->getMessage().PHP_EOL, FILE_APPEND);
            return json_encode([
                'result' => 'failed',
                'response' => $e->getMessage()
            ]);
        }
    }

    /**
     * @param string $range sheet range
     * @param string $valueInputOption google option for how the input value will be updated/inserted into the sheet
     * @param array $priority
     * @return json
     */
    public function addPriority(string $range, string $valueInputOption, array $priority){
        try{
            if (empty($this->spreadSheetId)){
                return json_encode([
                    'result' => 'failed',
                    'response' => 'Missing Spread Sheet ID',                    
                ]);
            }
            else if (empty($priority)){
                return json_encode([
                    'result' => 'failed',
                    'response' => 'Missing Priority',                    
                ]);
            }else if(empty($this->service_account_file_path)){
                return json_encode([
                    'result' => 'failed',
                    'response' => 'Missing Google Service Account File',                    
                ]);
            }else{
                putenv('GOOGLE_APPLICATION_CREDENTIALS='.$this->service_account_file_path);
                $client = new Google_Client();
                $client->useApplicationDefaultCredentials();
                $client->addScope(Google_Service_Sheets::SPREADSHEETS);
               
                $service = new Google_Service_Sheets($client);
    
                $body = new Google_Service_Sheets_ValueRange([
                    'values' => [$priority],
                ]);
    
                $params = [
                    'valueInputOption' => $valueInputOption,
                ];

                $result = $service->spreadsheets_values->update($this->spreadSheetId, $range, $body, $params);
     
                return json_encode([
                    'result' => 'success',
                    'response' => $result->getUpdatedCells()
                ]);
            }
        }catch(\Exception $e){
            file_put_contents('gsheet_error.txt','Gsheet Error: addPriority: '.PHP_EOL,FILE_APPEND);
            file_put_contents('gsheet_error.txt', $e->getMessage().PHP_EOL, FILE_APPEND);
            return json_encode([
                'result' => 'failed',
                'response' => $e->getMessage()
            ]);
        }
    }

    /**
     * @param string $range sheet range
     * @param string $valueInputOption google option for how the input value will be updated/inserted into the sheet
     * @param array $priority
     * @return json
     */
    public function assignTask(string $range, string $valueInputOption, array $user){
        try{
            if (empty($this->spreadSheetId)){
                return json_encode([
                    'result' => 'failed',
                    'response' => 'Missing Spread Sheet ID',                    
                ]);
            }
            else if (empty($user)){
                return json_encode([
                    'result' => 'failed',
                    'response' => 'Missing User',                    
                ]);
            }else if(empty($this->service_account_file_path)){
                return json_encode([
                    'result' => 'failed',
                    'response' => 'Missing Google Service Account File',                    
                ]);
            }else{
                putenv('GOOGLE_APPLICATION_CREDENTIALS='.$this->service_account_file_path);
                $client = new Google_Client();
                $client->useApplicationDefaultCredentials();
                $client->addScope(Google_Service_Sheets::SPREADSHEETS);
               
                $service = new Google_Service_Sheets($client);
    
                $body = new Google_Service_Sheets_ValueRange([
                    'values' => [$user],
                ]);
    
                $params = [
                    'valueInputOption' => $valueInputOption,
                ];
    
                $result = $service->spreadsheets_values->update($this->spreadSheetId, $range, $body, $params);
     
                return json_encode([
                    'result' => 'success',
                    'response' => $result->getUpdatedCells()
                ]);
            }
        }catch(\Exception $e){
            file_put_contents('gsheet_error.txt','Gsheet Error: assignTask: '.PHP_EOL,FILE_APPEND);
            file_put_contents('gsheet_error.txt', $e->getMessage().PHP_EOL, FILE_APPEND);
            return json_encode([
                'result' => 'failed',
                'response' => $e->getMessage()
            ]);
        }        
    }

    public function updatePhase(string $range, string $valueInputOption, string $phase){
        try{
            if (empty($this->spreadSheetId)){
                return json_encode([
                    'result' => 'failed',
                    'response' => 'Missing Spread Sheet ID',                    
                ]);
            }
            else if (empty($phase)){
                return json_encode([
                    'result' => 'failed',
                    'response' => 'Missing Phase',                    
                ]);
            }else if(empty($this->service_account_file_path)){
                return json_encode([
                    'result' => 'failed',
                    'response' => 'Missing Google Service Account File',                    
                ]);
            }else{
                putenv('GOOGLE_APPLICATION_CREDENTIALS='.$this->service_account_file_path);
                $client = new Google_Client();
                $client->useApplicationDefaultCredentials();
                $client->addScope(Google_Service_Sheets::SPREADSHEETS);
               
                $service = new Google_Service_Sheets($client);
    
                $body = new Google_Service_Sheets_ValueRange([
                    'values' => [[$phase]],
                ]);
    
                $params = [
                    'valueInputOption' => $valueInputOption,
                ];
    
                $result = $service->spreadsheets_values->update($this->spreadSheetId, $range, $body, $params);
     
                return json_encode([
                    'result' => 'success',
                    'response' => $result->getUpdatedCells()
                ]);
            }
        }catch(\Exception $e){
            file_put_contents('gsheet_error.txt','Gsheet Error: assignTask: '.PHP_EOL,FILE_APPEND);
            file_put_contents('gsheet_error.txt', $e->getMessage().PHP_EOL, FILE_APPEND);
            return json_encode([
                'result' => 'failed',
                'response' => $e->getMessage()
            ]);
        } 
    }

    /**
     * @param integer $startRow
     * @param integer $endRow
     * @return json
     */
    public function deleteTask(string $sheetId, int $startRow, int $endRow){
        try{
            putenv('GOOGLE_APPLICATION_CREDENTIALS='.$this->service_account_file_path);

            $client = new Google_Client();
            $client->useApplicationDefaultCredentials();
            $client->addScope(Google_Service_Sheets::SPREADSHEETS);
           
            $service = new Google_Service_Sheets($client);
            
            $deleteRequest = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
                'requests' => [
                    'deleteDimension' => [
                        'range' => [
                            'sheetId' => $sheetId,
                            'dimension' => "ROWS",
                            'startIndex' => $startRow,
                            'endIndex' => $endRow,
                        ]
                    ]
                ]
            ]);

            $response = $service->spreadsheets->batchUpdate($this->spreadSheetId, $deleteRequest);
            
            return json_encode([
                'result' => 'success',
                'response' => $response
            ]);
        }catch(\Exception $e){
            file_put_contents('gsheet_error.txt', $e->getMessage().PHP_EOL,FILE_APPEND);
            return json_encode([
                'result' => 'failed',
                'response' => $e->getMessage()
            ]);
        }
    }

    /**
     * @param string $findValue
     * @return json
     */
    public function getRowNumber(string $findValue){
        try{
            putenv('GOOGLE_APPLICATION_CREDENTIALS='.$this->service_account_file_path);

            $client = new Google_Client();
            $client->useApplicationDefaultCredentials();
            $client->addScope(Google_Service_Sheets::SPREADSHEETS);
           
            $service = new Google_Service_Sheets($client);
            
            $result = $service->spreadsheets_values->get($this->spreadSheetId, "C2:C");
            
            $amount_of_task = count($result->values);
            $row = 0;

            for($index = 0; $index < $amount_of_task; $index++){
                if (trim($result->values[$index][0]) === $findValue){
                    $row = $index+1; //+1 to account for how index/key starts at 0
                    break;
                }
            }

            if ($row === 0){
                return json_encode([
                    'result' => 'failed',
                    'response' => 'That task name does not exist'
                ]);
            }else{
                return json_encode([
                    'result' => 'success',
                    'response' => ($row+1) //+1 to account for the sheet starting row being at row 2
                ]);
            }

        }catch(\Exception $e){
            file_put_contents('gsheet_error.txt', $e->getMessage().PHP_EOL,FILE_APPEND);
            return json_encode([
                'result' => 'failed',
                'response' => $e->getMessage()
            ]);
        }
    }

    /**
     * @param string $filter
     * @return json
     */
    public function getTaskList(){
        try{
            putenv('GOOGLE_APPLICATION_CREDENTIALS='.$this->service_account_file_path);

            $client = new Google_Client();
            $client->useApplicationDefaultCredentials();
            $client->addScope(Google_Service_Sheets::SPREADSHEETS);
           
            $service = new Google_Service_Sheets($client);
            
            $result = $service->spreadsheets_values->get($this->spreadSheetId, "A2:J");
            
            $taskList = [
                'open' => [],
                'inprogress' => [],
                'complete' => [],
            ];

            foreach($result->values as $values){
                switch($values[1]){
                    case 'open':
                        $taskList['open'][] = $values[2];
                        break;
                    case 'inprogress':
                        $taskList['inprogress'][] = (isset($values[5])) ? $values[2].' > '.$values[5] : $values[2];
                        break;
                    case 'complete':
                        $taskList['complete'][] = $values[2].' > COMPLETE';
                        break;
                    default:
                        //all
                }                
            }

            return json_encode([
                'result' => 'success',
                'response' => $taskList
            ]);
        }catch(\Exception $e){
            file_put_contents('gsheet_error.txt', $e->getMessage().PHP_EOL,FILE_APPEND);
            return json_encode([
                'result' => 'failed',
                'response' => $e->getMessage()
            ]);
        }
    }

    /**
     * @param string $filter
     * @return json
     */
    public function getTaskListNoFilter(){
        try{
            putenv('GOOGLE_APPLICATION_CREDENTIALS='.$this->service_account_file_path);

            $client = new Google_Client();
            $client->useApplicationDefaultCredentials();
            $client->addScope(Google_Service_Sheets::SPREADSHEETS);
           
            $service = new Google_Service_Sheets($client);
            
            $result = $service->spreadsheets_values->get($this->spreadSheetId, "A2:J");
            
            return json_encode([
                'result' => 'success',
                'response' => $result->values
            ]);
        }catch(\Exception $e){
            file_put_contents('gsheet_error.txt', $e->getMessage().PHP_EOL,FILE_APPEND);
            return json_encode([
                'result' => 'failed',
                'response' => $e->getMessage()
            ]);
        }
    }

    /**
     * @param string $assigned_user
     * @return json
     */
    public function getUserTaskList(string $assigned_user){
        try{
            putenv('GOOGLE_APPLICATION_CREDENTIALS='.$this->service_account_file_path);

            $client = new Google_Client();
            $client->useApplicationDefaultCredentials();
            $client->addScope(Google_Service_Sheets::SPREADSHEETS);
           
            $service = new Google_Service_Sheets($client);
            
            $result = $service->spreadsheets_values->get($this->spreadSheetId, "A2:J");
            
            $taskList = [
                'open' => [],
                'inprogress' => [],
                'complete' => [],
            ];

            if (!empty($result->values)){
                foreach($result->values as $values){
                    if (isset($values[5])){
                        if ($values[5] === $assigned_user){
                            switch($values[1]){
                                case 'inprogress':
                                    $taskList['inprogress'][] = "Task: ".$values[2].PHP_EOL."Priority: ".(isset($values[4])?$values[4]:'none').PHP_EOL."Phase: ".$values[1].PHP_EOL."Description: ".(isset($values[3])?$values[3]:'none');
                                    break;
                                case 'complete':
                                    $taskList['complete'][] = $values[2].' > COMPLETE';
                                    break;
                                default:
                                    //all
                            }  
                        }
                    }
                 
                }
            }

            return json_encode([
                'result' => 'success',
                'response' => $taskList
            ]);
        }catch(\Exception $e){
            file_put_contents('gsheet_error.txt', $e->getMessage().PHP_EOL,FILE_APPEND);
            return json_encode([
                'result' => 'failed',
                'response' => $e->getMessage()
            ]);
        }
    }

    /**
     * @return json
     */
    public function getSheetId(){
        try{
            putenv('GOOGLE_APPLICATION_CREDENTIALS='.$this->service_account_file_path);

            $client = new Google_Client();
            $client->useApplicationDefaultCredentials();
            $client->addScope(Google_Service_Sheets::SPREADSHEETS);
           
            $service = new Google_Service_Sheets($client);
            
            $result = $service->spreadsheets->get($this->spreadSheetId);

            $mainSheetId = $result->sheets[0]->properties->sheetId;
    
            return json_encode([
                'result' => 'success',
                'response' => $mainSheetId
            ]);
        }catch(\Exception $e){
            file_put_contents('gsheet_error.txt', $e->getMessage().PHP_EOL,FILE_APPEND);
            return json_encode([
                'result' => 'failed',
                'response' => $e->getMessage()
            ]);
        }
    }

    /**
     * @return json
     */
    public function getUsersInfo(){
        try{
            putenv('GOOGLE_APPLICATION_CREDENTIALS='.$this->service_account_file_path);

            $client = new Google_Client();
            $client->useApplicationDefaultCredentials();
            $client->addScope(Google_Service_Sheets::SPREADSHEETS);
           
            $service = new Google_Service_Sheets($client);
            
            $result = $service->spreadsheets_values->get($this->spreadSheetId, "Users!A2:C");
            
            return json_encode([
                'result' => 'success',
                'response' => $result->values
            ]);
        }catch(\Exception $e){
            file_put_contents('gsheet_error.txt', $e->getMessage().PHP_EOL,FILE_APPEND);
            return json_encode([
                'result' => 'failed',
                'response' => $e->getMessage()
            ]);
        }
    }


    /**
     * @param string $range sheet range
     * @param string $valueInputOption google option for how the input value will be updated/inserted into the sheet
     * @param array $priority
     * @return json
     */
    public function setDate(string $range, string $valueInputOption, string $date){
        try{
            if (empty($this->spreadSheetId)){
                return json_encode([
                    'result' => 'failed',
                    'response' => 'Missing Spread Sheet ID',                    
                ]);
            }
            else if(empty($this->service_account_file_path)){
                return json_encode([
                    'result' => 'failed',
                    'response' => 'Missing Google Service Account File',                    
                ]);
            }else{
                putenv('GOOGLE_APPLICATION_CREDENTIALS='.$this->service_account_file_path);
                $client = new Google_Client();
                $client->useApplicationDefaultCredentials();
                $client->addScope(Google_Service_Sheets::SPREADSHEETS);
               
                $service = new Google_Service_Sheets($client);
    
                $body = new Google_Service_Sheets_ValueRange([
                    'values' => [[$date]],
                ]);
    
                $params = [
                    'valueInputOption' => $valueInputOption,
                ];
    
                $result = $service->spreadsheets_values->update($this->spreadSheetId, $range, $body, $params);
     
                return json_encode([
                    'result' => 'success',
                    'response' => $result->getUpdatedCells()
                ]);
            }
        }catch(\Exception $e){
            file_put_contents('gsheet_error.txt','Gsheet Error: setModifiedDate: '.PHP_EOL,FILE_APPEND);
            file_put_contents('gsheet_error.txt', $e->getMessage().PHP_EOL, FILE_APPEND);
            return json_encode([
                'result' => 'failed',
                'response' => $e->getMessage()
            ]);
        }        
    }

    /**
     * @param string $task
     * @return json
     */
    public function checkTaskName(string $task){
        try{
            putenv('GOOGLE_APPLICATION_CREDENTIALS='.$this->service_account_file_path);

            $client = new Google_Client();
            $client->useApplicationDefaultCredentials();
            $client->addScope(Google_Service_Sheets::SPREADSHEETS);
           
            $service = new Google_Service_Sheets($client);
            
            $result = $service->spreadsheets_values->get($this->spreadSheetId, "A2:J");
            
            $task_exist = false;
            foreach($result->values as $values){
                if ($values[2] == $task){
                    $task_exist = true;
                    break;
                }
            }

            if ($task_exist == false){
                return json_encode([
                    'result' => 'success',
                    'response' => null
                ]);
            }else{
                return json_encode([
                    'result' => 'failed',
                    'response' => 'Task name already exists. Please choose a unique name for your task or delete the other one.'
                ]);
            }
        }catch(\Exception $e){
            file_put_contents('gsheet_error.txt', $e->getMessage().PHP_EOL,FILE_APPEND);
            return json_encode([
                'result' => 'failed',
                'response' => $e->getMessage()
            ]);
        }
    }


}