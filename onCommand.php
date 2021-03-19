<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/mFolder/mconfig.php';
require __DIR__ . '/gsheets.php';

if ($_GET['skey'] === SECRET_PATH_KEY){
    try{
        $telegram = new \TelegramBot\Api\BotApi(TOKEN);

        $tgobject = json_decode(file_get_contents('php://input'));

        $chat_id = $tgobject->message->chat->id;
        $reply_to_message_id = $tgobject->message->message_id;

        $telegram_text_explode = explode(" ",$tgobject->message->text);
        $telegram_command = strtolower($telegram_text_explode[0]);
        $from_user_id = $tgobject->message->from->id;
        $from_user = $tgobject->message->from->first_name;

        //TODO use $chat_type to discern between users on group chat vs users one v. one
        $chat_type = $tgobject->message->chat->type;
        
        if (isset($tgobject->message->reply_to_message->text)){
            $message_sent = explode(':',$tgobject->message->reply_to_message->text);
            $user_reply = $tgobject->message->text;
        }
        

        $gsheet = new Gsheets();
        $user_info_result = json_decode($gsheet->getUsersInfo());        
        
        if ($user_info_result->result == 'success'){
            $allow_command = false;
            foreach($user_info_result->response as $value){
                if (!empty($value[0]) && !empty($value[1])){
                    if ($value[0] == $chat_id && $value[1] == $from_user_id){
                        $allow_command = true;
                        break;
                    }
                }
            }

            switch($telegram_command){
                case '/chatinfo':
                    $telegram->sendMessage($chat_id, "Your Chat ID: ".PHP_EOL.$chat_id.PHP_EOL."User ID: ".PHP_EOL.$from_user_id.PHP_EOL."name: ".PHP_EOL.$from_user, null, false, $reply_to_message_id);
                    break;
                case '/prepsheet':
                    //connect to Google Task Sheet
                    $sheet = $gsheet->setSpreadSheet("USER_ENTERED",[
                        ['Date Created', 'Phase', 'Task Name', 'Description', 'Priority', 'Assigned', 'Assigned By', 'Date Assigned', 'Date Modified', 'Date Completed']
                    ]);
        
                    $result = json_decode($sheet);
        
                    if ($result->result == 'success'){
                        $telegram->sendMessage($chat_id, "Your Google Sheets Setup is complete", null, false, $reply_to_message_id);
                    }else{
                        $telegram->sendMessage($chat_id, "Error: was unable to Setup your Google Sheet. Please try again, if issue persist contact IT : ".$result->response, null, false, $reply_to_message_id);
                    }
                    /////////////////// 
                    break;
                default:
                    if ($allow_command){
                        if ($telegram_command === '/view'){
                            $keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup(
                                [
                                ["Tasks"],
                                ["Open"],
                                ["InProgress"],
                                ["Complete"],
                                ["All"],
                                ], true,null,true);
                            
                            $telegram->sendMessage($chat_id, "View Options: ", null, false, $reply_to_message_id, $keyboard);
                        }
                    
                        if ($telegram_command === '/create'){
                
                            if (count($telegram_text_explode) > 1){
                                $task = '';
                                foreach($telegram_text_explode as $key=>$value){
                                    if ($key != 0){
                                        $task .= $value." ";  
                                    }
                                }
                
                                $check_task_name_result = json_decode($gsheet->checkTaskName(trim(strtolower($task))));
                
                                if ($check_task_name_result->result == 'success'){
                                    $dateTime = new DateTime(date('Y-m-d H:i:s'), new DateTimeZone('UTC'));
                                    $date = $dateTime->format('Y-m-d H:i:s');
                    
                                    $basic_task_fields = [
                                        $date,
                                        'open',
                                        trim($task)
                                    ];
                    
                                    $create_task_result = json_decode($gsheet->createTask('A2', 'USER_ENTERED', $basic_task_fields));
                    
                                    if ($create_task_result->result == 'success'){
                                        $force_reply = new \TelegramBot\Api\Types\ForceReply(true,true);
                    
                                        $telegram->sendMessage($chat_id, "Task: '".$task."' has been created.", null, false, $reply_to_message_id);
                                        $telegram->sendMessage($chat_id, "/description :".$task, null, false, $reply_to_message_id, $force_reply);
                                    }else{
                                        $telegram->sendMessage($chat_id, "Failed to create: '".$task."' :".$create_task_result->response, null, false, $reply_to_message_id);
                                    }
                                }else{
                                    $telegram->sendMessage($chat_id, "Failed to create: '".$task."' :".$check_task_name_result->response, null, false, $reply_to_message_id);
                                }
                
                            }else{
                                $telegram->sendMessage($chat_id, "Invalid Request: Please enter a task along with the /create command.", null, false, $reply_to_message_id);
                            }
                        }
                
                        if ($telegram_command === '/description'){
                            if (!isset($message_sent)){
                                if (count($telegram_text_explode) > 1){
                
                                    $task = '';
                                    foreach($telegram_text_explode as $key=>$value){
                                        if ($key != 0){
                                            $task .= $value." ";  
                                        }
                                    }
                
                                    $force_reply = new \TelegramBot\Api\Types\ForceReply(true,true);
                
                                    $telegram->sendMessage($chat_id, "/description :".$task.":Enter Description", null, false, $reply_to_message_id, $force_reply);
                                }else{
                                    $telegram->sendMessage($chat_id, "Invalid Request: Please enter a task along with the /description command.", null, false, $reply_to_message_id);
                                }
                            }
                        }
                
                        if ($telegram_command === '/priority'){
                
                            if (!isset($message_sent)){
                                if (count($telegram_text_explode) > 1){
                
                                    $task = '';
                                    foreach($telegram_text_explode as $key=>$value){
                                        if ($key != 0){
                                            $task .= $value." ";  
                                        }
                                    }

                                    $keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup(
                                        [
                                            ["High"],
                                            ["Medium"],
                                            ["Low"],
                                            ["None"],
                                        ], true,null,true); 

                
                                    $telegram->sendMessage($chat_id, "/priority :".$task.":Please Select a Priority", null, false, $reply_to_message_id, $keyboard);
                                }else{
                                    $telegram->sendMessage($chat_id, "Invalid Request: Please enter a task along with the /priority command.", null, false, $reply_to_message_id);
                                }
                            }
                        }
                
                
                        if ($telegram_command === '/take'){
                            if (!isset($message_sent)){
                                $openTasksResult = json_decode($gsheet->getTaskList());
                                if ($openTasksResult->result == 'success'){
                                    $task = $openTasksResult->response;
                    
                                    $openList = [];
                                    foreach($task->open as $open){
                                        $openList[] = [$open];
                                    }
                                    $keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup($openList, true);
                        
                                    $telegram->sendMessage($chat_id, "/take :Choose a task to do", null, false, $reply_to_message_id, $keyboard);                    
                                }else{
                                    $telegram->sendMessage($chat_id, "An error has occurred please try again in a bit. If issue persist contact IT: ".$openTasksResult->response, null, false, $reply_to_message_id);
                                }
                            }
                        }
                
                        if ($telegram_command === '/user'){
                            if (!isset($message_sent)){
                                $userListResult = json_decode($gsheet->getUsersInfo());
                                if ($userListResult->result == 'success'){
                                    $userList = $userListResult->response;
                    
                                    $users = [];
                                    foreach($userList as $user){
                                        $user_exist = false;
                                        foreach($users as $value){
                                            if ($value[0] === $user[2]){
                                                $user_exist = true;
                                                break;
                                            }
                                        }

                                        if ($user_exist == false){
                                            $users[] = [$user[2]];
                                        }
                                    }

                                    $keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup($users, true);
                        
                                    $telegram->sendMessage($chat_id, "/user :Choose a user", null, false, $reply_to_message_id, $keyboard);                    
                                }else{
                                    $telegram->sendMessage($chat_id, "An error has occurred please try again in a bit. If issue persist contact IT: ".$openTasksResult->response, null, false, $reply_to_message_id);
                                }
                            }
                        }
                    
                        if ($telegram_command === '/done'){
                            $openTasksResult = json_decode($gsheet->getTaskList());
                
                            if ($openTasksResult->result === 'success'){
                                $task = $openTasksResult->response;
                
                                $taskList = [];
                
                                foreach($task->open as $open){
                                    $taskList[] = [$open];
                                }
                
                                foreach($task->inprogress as $inprogress){
                                    $taskList[] = [$inprogress];
                                }
                
                                $keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup($taskList, true);
                                
                                $telegram->sendMessage($chat_id, "/done :Mark a task as complete ", null, false, $reply_to_message_id, $keyboard);
                            }else{
                                $telegram->sendMessage($chat_id, "Error: unable to view open task. Please contact your IT if issue persist.", $reply_to_message_id);
                            }
                
                                        
                        }
                    
                        if ($telegram_command === '/delete'){
                
                            $openTasksResult = json_decode($gsheet->getTaskListNoFilter());
                
                            if ($openTasksResult->result === 'success'){
                                $task = $openTasksResult->response;
                
                
                                $taskList = [];
                                foreach($task as $task_name){
                                    $taskList[] = [$task_name[2]];
                                }
                
                                $keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup($taskList, true);
                                
                                $telegram->sendMessage($chat_id, "/delete :Choose a task to delete ", null, false, $reply_to_message_id, $keyboard);
                            }else{
                                $telegram->sendMessage($chat_id, "Error: unable to delete task. Please contact your IT if issue persist.", $reply_to_message_id);
                            }
                        }
                
                        //help list
                        if ($telegram_command === '/help'){
                            $telegram->sendMessage($chat_id, "These are my available commands: ".PHP_EOL.
                                "/view - use this if you want to view tasks".PHP_EOL."/create - use this if you want to create a new task".PHP_EOL."/take - use this if you want to take a specific task".PHP_EOL.
                                "/done - use this to mark task as complete".PHP_EOL."/delete - use this if you want to delete a task".PHP_EOL."/priority - use this to set level of urgency to task".PHP_EOL.
                                "/description - use this to describe what needs to be done on the task".PHP_EOL."/prepsheet - only do this if your sheet is missing the task headers".PHP_EOL, 
                            null, false, $reply_to_message_id);
                        }          
                        //////////////        
                
                        //view options
                        if ($telegram_command === 'tasks'){
                
                            $openTasksResult = json_decode($gsheet->getUserTaskList(trim(strtolower($from_user))));
                
                            if ($openTasksResult->result === 'success'){
                                $task = $openTasksResult->response;
                
                                $taskList = '';
                                foreach($task->inprogress as $inprogress){
                                    $taskList .= $inprogress.PHP_EOL.PHP_EOL;
                                }
                
                                foreach($task->complete as $complete){
                                    $taskList .= $complete.PHP_EOL;
                                }
                
                                //show all open task
                                $telegram->sendMessage($chat_id, "These are Your tasks: ".PHP_EOL.(!empty($taskList)?$taskList:'-You have no tasks to do-'), null, false, $reply_to_message_id);
                            }else{
                                $telegram->sendMessage($chat_id, "Error: unable to view open task. Please contact your IT if issue persist.", $reply_to_message_id);
                            }
                        }
                
                        if ($telegram_command === 'open'){
                
                            $openTasksResult = json_decode($gsheet->getTaskList());
                
                            if ($openTasksResult->result === 'success'){
                                $task = $openTasksResult->response;
                
                                $taskList = '';
                                foreach($task->open as $open){
                                    $taskList .= $open.PHP_EOL;
                                }
                
                                //show all open task
                                $telegram->sendMessage($chat_id, "Open tasks: ".PHP_EOL.$taskList, null, false, $reply_to_message_id);
                            }else{
                                $telegram->sendMessage($chat_id, "Error: unable to view open task. Please contact your IT if issue persist.", $reply_to_message_id);
                            }
                        }
                        
                        if ($telegram_command === 'inprogress'){
                
                            $openTasksResult = json_decode($gsheet->getTaskList());
                
                            if ($openTasksResult->result === 'success'){
                                $task = $openTasksResult->response;
                
                                $taskList = '';
                                foreach($task->inprogress as $inprogress){
                                    $taskList .= $inprogress.PHP_EOL;
                                }
                
                                //show all assigned task
                                $telegram->sendMessage($chat_id, "These are the tasks that are In Progress: ".PHP_EOL.$taskList, null, false, $reply_to_message_id);
                            }else{
                                $telegram->sendMessage($chat_id, "Error: unable to view open task. Please contact your IT if issue persist.", $reply_to_message_id);
                            }
                        }
                
                        if ($telegram_command === 'complete'){
                
                            $openTasksResult = json_decode($gsheet->getTaskList());
                
                            if ($openTasksResult->result === 'success'){
                                $task = $openTasksResult->response;
                
                                $taskList = '';
                                foreach($task->complete as $complete){
                                    $taskList .= $complete.PHP_EOL;
                                }
                
                                //show all completed task
                                $telegram->sendMessage($chat_id, "These are the tasks that has been completed: ".PHP_EOL.$taskList, null, false, $reply_to_message_id);
                            }else{
                                $telegram->sendMessage($chat_id, "Error: unable to view open task. Please contact your IT if issue persist.", $reply_to_message_id);
                            }
                        }
                
                        if ($telegram_command === 'all'){
                
                            $openTasksResult = json_decode($gsheet->getTaskList());
                
                            if ($openTasksResult->result === 'success'){
                                $task = $openTasksResult->response;
                
                                $taskList = '';
                
                                foreach($task->open as $open){
                                    $taskList .= $open.PHP_EOL;
                                }
                
                                foreach($task->inprogress as $inprogress){
                                    $taskList .= $inprogress.PHP_EOL;
                                }
                
                                foreach($task->complete as $complete){
                                    $taskList .= $complete.PHP_EOL;
                                }
                
                                //show all task
                                $telegram->sendMessage($chat_id, "These are the all the tasks: ".PHP_EOL.$taskList, null, false, $reply_to_message_id);
                            }else{
                                $telegram->sendMessage($chat_id, "Error: unable to view open task. Please contact your IT if issue persist.", $reply_to_message_id);
                            }
                        }
                
                        //////////////
                
                        //Reply back action on typed input
                        if(isset($message_sent)){
                            $dateTime = new DateTime(date('Y-m-d H:i:s'), new DateTimeZone('UTC'));
                            $date = $dateTime->format('Y-m-d H:i:s');
                            switch(trim($message_sent[0])){
                                case '/description':
                                    $row_number = json_decode($gsheet->getRowNumber(trim(strtolower($message_sent[1]))));
                                    if ($row_number->result == 'success'){
                                        $add_task_desc = json_decode($gsheet->addDescription('D'.$row_number->response, 'USER_ENTERED', [$user_reply]));
                                        $set_mod_date = json_decode($gsheet->setDate('I'.$row_number->response, 'USER_ENTERED', $date));
                                        if ($add_task_desc->result == 'success' && $set_mod_date->result == 'success'){
                                            if (!isset($message_sent[2])){
                                                $telegram->sendMessage($chat_id, "Description :".$user_reply."' has been added.", null, false, $reply_to_message_id);
                                                $keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup(
                                                    [
                                                        ["High"],
                                                        ["Medium"],
                                                        ["Low"],
                                                        ["None"],
                                                    ], true,null,true);                        
                                                
                                                $telegram->sendMessage($chat_id, "/priority :".$message_sent[1], null, false, $reply_to_message_id, $keyboard);
                                            }else{
                                                $telegram->sendMessage($chat_id, "Description for task: '".$message_sent[1]."' is now updated", null, false, $reply_to_message_id);
                                            }
                                        
                                        }else{
                                            $telegram->sendMessage($chat_id, "Failed to set Description: '".$user_reply."' :".$add_task_desc->response, null, false, $reply_to_message_id);
                                        }
                                    }else{
                                        $telegram->sendMessage($chat_id, "Failed to set Description: '".$user_reply."' :".$row_number->response, null, false, $reply_to_message_id);
                                    }
                                    
                                    break;
                                case '/priority':
                                    if (trim(strtolower($user_reply)) ===  'high' ||trim(strtolower($user_reply)) ===  'medium' || trim(strtolower($user_reply)) ===  'low' || trim(strtolower($user_reply)) ===  'none' ){
                                        $row_number = json_decode($gsheet->getRowNumber(trim(strtolower($message_sent[1]))));
                                        if ($row_number->result == 'success'){
                                            $force_reply = new \TelegramBot\Api\Types\ForceReply(true,true);
                                            $add_priority = json_decode($gsheet->addPriority('E'.$row_number->response, 'USER_ENTERED', [strtolower($user_reply)]));
                                            $set_mod_date = json_decode($gsheet->setDate('I'.$row_number->response, 'USER_ENTERED', $date));
                                            if ($add_priority->result == 'success' && $set_mod_date->result == 'success'){
                                                if (!isset($message_sent[2])){
                                                    $telegram->sendMessage($chat_id, "Priority :".$user_reply."' has been added.", null, false, $reply_to_message_id);
                                                    $telegram->sendMessage($chat_id, "/assign :".$message_sent[1].":Enter person to assign to", null, false, $reply_to_message_id, $force_reply);
                                                }else{
                                                    $telegram->sendMessage($chat_id, "Priority for task: '".$message_sent[1]."' is now updated", null, false, $reply_to_message_id);
                                                }
                                            }else{
                                                $telegram->sendMessage($chat_id, "Failed to set Priority: '".$user_reply."' :".$add_priority->response, null, false, $reply_to_message_id);
                                            }
                                        }else{
                                            $telegram->sendMessage($chat_id, "Failed to set Priority: '".$user_reply."' :".$row_number->response, null, false, $reply_to_message_id);
                                        }
                                    }else{
                                        $telegram->sendMessage($chat_id, "Invalid Priority input: '".$user_reply, null, false, $reply_to_message_id);
                                    }
                                    break;
                                case '/assign':
                                    $row_number = json_decode($gsheet->getRowNumber(trim(strtolower($message_sent[1]))));
                                    if ($row_number->result == 'success'){
                                        $update_phase = json_decode($gsheet->updatePhase('B'.$row_number->response, 'USER_ENTERED','inprogress'));
                                        $assign_task_to_user = json_decode($gsheet->assignTask('F'.$row_number->response, 'USER_ENTERED', [strtolower($user_reply),strtolower($from_user),$date]));
                                        $set_mod_date = json_decode($gsheet->setDate('I'.$row_number->response, 'USER_ENTERED', $date));
                                        if ($assign_task_to_user->result == 'success' && $set_mod_date->result == 'success' && $update_phase->result == 'success'){
                                            $telegram->sendMessage($chat_id, "Assigned ".$user_reply."' to task - ".$message_sent[1], null, false, $reply_to_message_id);
                                        }else{
                                            $telegram->sendMessage($chat_id, "Failed to Assign: '".$user_reply."' :".PHP_EOL." Phase -".$update_phase->response.PHP_EOL."Assign -".$assign_task_to_user->response.PHP_EOL."Mod Date -".$set_mod_date->response, null, false, $reply_to_message_id);
                                        }
                                    }else{
                                        $telegram->sendMessage($chat_id, "Failed to Assign: '".$user_reply."' :".$row_number->response, null, false, $reply_to_message_id);
                                    }
                                    break;
                                case '/take':
                                    $row_number = json_decode($gsheet->getRowNumber(trim(strtolower($user_reply))));
                                    if ($row_number->result == 'success'){
                                        $update_phase = json_decode($gsheet->updatePhase('B'.$row_number->response, 'USER_ENTERED','inprogress'));
                                        $assign_task_to_user = json_decode($gsheet->assignTask('F'.$row_number->response, 'USER_ENTERED', [strtolower($from_user),strtolower($from_user),$date]));
                                        $set_mod_date = json_decode($gsheet->setDate('I'.$row_number->response, 'USER_ENTERED', $date));
                                        if ($assign_task_to_user->result == 'success' && $set_mod_date->result == 'success' && $update_phase->result == 'success'){
                                            $telegram->sendMessage($chat_id, "User ".$from_user."' chose to take task - ".$user_reply, null, false, $reply_to_message_id);
                                        }else{
                                            $telegram->sendMessage($chat_id, "Failed to Take task: '".$user_reply."' :".PHP_EOL." Phase -".$update_phase->response.PHP_EOL."Assign -".$assign_task_to_user->response.PHP_EOL."Mod Date -".$set_mod_date->response, null, false, $reply_to_message_id);
                                        }                        
                                    }else{
                                        $telegram->sendMessage($chat_id, "Failed to Take Task: '".$user_reply."' :".$row_number->response, null, false, $reply_to_message_id);
                                    }
                                    break;
                                case '/done':
                                    $row_number = json_decode($gsheet->getRowNumber(trim(strtolower($user_reply))));
                                    if ($row_number->result == 'success'){
                                        $update_phase = json_decode($gsheet->updatePhase('B'.$row_number->response, 'USER_ENTERED','complete'));
                                        $set_complete_date = json_decode($gsheet->setDate('J'.$row_number->response, 'USER_ENTERED', $date));
                                        if ($set_complete_date->result == 'success' && $update_phase->result == 'success'){
                                            $telegram->sendMessage($chat_id, "User ".$from_user."' marked task - ".$user_reply." - as complete", null, false, $reply_to_message_id);
                                        }else{
                                            $telegram->sendMessage($chat_id, "Failed to Take task: '".$user_reply."' :".PHP_EOL." Phase -".$update_phase->response.PHP_EOL."Complete Date -".$set_complete_date->response, null, false, $reply_to_message_id);
                                        }     
                                    }else{
                                        $telegram->sendMessage($chat_id, "Failed to Mark Complete: '".$user_reply."' :".$row_number->response, null, false, $reply_to_message_id);
                                    }
                                    break;
                                case '/delete':
                                    $row_number = json_decode($gsheet->getRowNumber(trim(strtolower($user_reply))));
                                    if ($row_number->result == 'success'){
                                        $sheetId = json_decode($gsheet->getSheetId());
                                        $delete_task_result = json_decode($gsheet->deleteTask($sheetId->response, $row_number->response-1,$row_number->response));
                                        if ($delete_task_result->result == 'success' && $sheetId->result == 'success'){
                                            $telegram->sendMessage($chat_id, "User ".$from_user."' has deleted - ".$user_reply, null, false, $reply_to_message_id);
                                        }else{
                                            $telegram->sendMessage($chat_id, "Failed to delete task: '".$user_reply."' :".PHP_EOL.$delete_task_result->response.PHP_EOL." SheetId - ".$sheetId->response, null, false, $reply_to_message_id);
                                        }  
                                    }else{
                                        $telegram->sendMessage($chat_id, "Failed to Delete Task: '".$user_reply."' :".$row_number->response, null, false, $reply_to_message_id);
                                    }
                                    break;
                                case '/user':
                                    $force_reply = new \TelegramBot\Api\Types\ForceReply(true,true);
                                    if (empty($message_sent[2])){
                                        $telegram->sendMessage($chat_id, "/user :".$user_reply." :Enter task to assign", null, false, $reply_to_message_id,$force_reply);
                                    }else{
                                        $row_number = json_decode($gsheet->getRowNumber(trim(strtolower($user_reply))));
                                        if ($row_number->result == 'success'){
                                            $update_phase = json_decode($gsheet->updatePhase('B'.$row_number->response, 'USER_ENTERED','inprogress'));
                                            $assign_task_to_user = json_decode($gsheet->assignTask('F'.$row_number->response, 'USER_ENTERED', [strtolower($message_sent[1]),strtolower($from_user),$date]));
                                            $set_mod_date = json_decode($gsheet->setDate('I'.$row_number->response, 'USER_ENTERED', $date));
                                            if ($assign_task_to_user->result == 'success' && $set_mod_date->result == 'success' && $update_phase->result == 'success'){
                                                $telegram->sendMessage($chat_id, "Assigned ".$message_sent[1]." to task - ".$user_reply, null, false, $reply_to_message_id);
                                                
                                                $user_list = json_decode($gsheet->getUsersInfo());
                                                foreach($user_list->response as $value){
                                                    if (trim($message_sent[1]) === $value[2]){
                                                        $telegram->sendMessage($value[0], "You have been assigned the following task - ".$user_reply, null, false);
                                                        break;
                                                    }
                                                }
                                            }else{
                                                $telegram->sendMessage($chat_id, "Failed to Assign: '".$message_sent[1]."' :".PHP_EOL." Phase -".$update_phase->response.PHP_EOL."Assign -".$assign_task_to_user->response.PHP_EOL."Mod Date -".$set_mod_date->response, null, false, $reply_to_message_id);
                                            }
                                        }else{
                                            $telegram->sendMessage($chat_id, "Failed to Assign: '".$message_sent[1]."' :".$row_number->response, null, false, $reply_to_message_id);
                                        }
                                    }
                                    
                                    break;
                            }
                        }
                        ///////////////
                

                    }else{
                        $telegram->sendMessage($chat_id, "Who are you?", null, false, $reply_to_message_id);
                    }
            }

        }else{
            $telegram->sendMessage($chat_id, "....", null, false, $reply_to_message_id);
        }


        header("HTTP/1.1 200 OK");   
    
    }catch(Longman\TelegramBot\Exception\TelegramException $e){
        file_put_contents('tg_onCommand_error.txt', $e->getMessage().PHP_EOL,FILE_APPEND);
        echo $e->getMessage();
    }
}
